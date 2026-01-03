**TaskFlow Postman Test Walkthrough**

This guide explains how to import and run the TaskFlow Postman collection and environment, and how to test reminders which rely on queues and scheduler.

**Prerequisites**
- PHP, Composer and Laravel installed.
- Database configured in `.env`.
- Migrations run:
  - `php artisan migrate`
- Queue DB tables created (if using `database` queue connection):
  - `php artisan queue:table`
  - `php artisan migrate` (to create jobs/failed_jobs tables)
- Start the local server:
  - `php artisan serve` (defaults to http://127.0.0.1:8000)
- Ensure `.env` has `QUEUE_CONNECTION=database` when testing reminders.

Files created (ready to import):
- `postman/TaskFlow.postman_collection.json`
- `postman/TaskFlow.local.postman_environment.json`

Import steps
1. Open Postman.
2. Import the collection:
   - File → Import → Choose `postman/TaskFlow.postman_collection.json`.
3. Import the environment:
   - Environments → Import → Choose `postman/TaskFlow.local.postman_environment.json`.
4. Select the `TaskFlow.local` environment in the top-right environment selector.

Environment variables overview
- `base_url`: http://127.0.0.1:8000
- `api_prefix`: /api
- `userA_email`, `userA_password`: credentials for User A (defaults in environment)
- `userB_email`, `userB_password`: credentials for User B
- `tokenA`, `tokenB`, `userA_id`, `userB_id`
- `workspace_id`, `project_id`, `task_id`, `comment_id`, `reminder_id`
- `now_plus_1_minute`: optionally computed in pre-request scripts (see below)
- `role_member`, `role_admin`: role strings

Important note about a small mismatch in the code:
- The `CommentController` writes `body` to DB, while the `CommentStoreRequest` expects `content`. To avoid server errors we include both `content` and `body` in the comment request body. The collection's "Add Comment" request does this already.

How to run the collection (recommended order)
Use the Collection Runner in Postman and run the folders/requests in the order below. The collection structure mirrors this order; run sequentially:

1. Folder: `Auth`
   - Register User A
   - Register User B
   - Login User A  (stores `tokenA` & `userA_id`)
   - Login User B  (stores `tokenB` & `userB_id`)
   - Me (User A)
   - Logout (User A) — optional (will clear `tokenA` in environment)

2. Folder: `Workspace (User A)`
   - Create Workspace (saves `workspace_id`)
   - List Workspaces
   - Get Workspace
   - Update Workspace

3. Folder: `Workspace Membership + Role Rules`
   - Get Workspace as User B BEFORE add (expects 403)
   - Add Member (adds `userB_email` as member)
   - Get Workspace as User B AFTER add (expects 200)
   - Update Member Role to admin (expects 200)
   - Duplicate Add Member (expects 409)
   - Remove Member (expects 200)
   - Get Workspace as User B AFTER removal (expects 403)

4. Folder: `Projects (User A)`
   - Create Project under Workspace (saves `project_id`)
   - List Projects under Workspace
   - Get Project
   - Update Project

5. Folder: `Tasks`
   - Create Task under Project (saves `task_id`)
   - List Tasks with filters (status/priority/q)
   - Get Task (should include `comments` and `reminders` arrays)
   - Update Task

6. Folder: `Comments`
   - Add Comment (saves `comment_id` if returned)
   - Get Task again and assert comment exists

7. Folder: `Reminders + Queue Test`
   - Create Reminder for near-future (saves `reminder_id`).
     - Pre-request script computes `now_plus_1_minute` if empty and sets it to an ISO timestamp (e.g. `2026-01-03T12:00:00Z`).
     - The request sends `{ "runs_at": "{{now_plus_1_minute}}" }`.
   - Manual: Wait 70 seconds, then run "Manual: Wait and Check Reminder sent_at" request (GET task) to verify `reminders[].sent_at` updated.

Queue & scheduler manual steps (to make reminders fire)
- Terminal A: run queue worker(s):
  - `php artisan queue:work --queue=high,default,low`
- Terminal B: run scheduler worker (so scheduled tasks that dispatch reminders run):
  - `php artisan schedule:work`

Important: ensure `QUEUE_CONNECTION=database` in `.env` and that `jobs` & `failed_jobs` tables exist (`php artisan queue:table` then `php artisan migrate`).

Notes about validations & expectations (derived from code)
- Auth:
  - `POST /api/auth/register` requires `name`, `email`, `password`, `password_confirmation`. Returns 201 with `token` and `user`.
  - `POST /api/auth/login` requires `email`, `password`. Returns `token` and `user`.
- Workspaces:
  - `POST /api/workspaces` requires `name`.
  - `PATCH /api/workspaces/{workspace}` accepts `name` (sometimes).
  - Access control:
    - Only members (owner|admin|member) may `GET` workspace.
    - Only admins (owner|admin) may `POST` members or update members.
    - Owner-only for deleting workspace.
- Members:
  - `POST /api/workspaces/{workspace}/members` expects `email` (exists in users table) and optional `role` in: `admin,member`. Duplicate member returns 409 (controller aborts).
  - `PATCH /api/workspaces/{workspace}/members/{user}` expects `role` required `admin|member`.
  - Removing owner via endpoints is blocked (400).
- Projects:
  - `POST /api/workspaces/{workspace}/projects` expects `name`.
  - `PATCH /api/projects/{project}` accepts `name` (sometimes).
- Tasks:
  - `POST /api/projects/{project}/tasks` expects `title` (+ optional `description`,`status` in `todo|doing|done`,`priority` in `low|medium|high`,`due_at` date).
  - `GET /api/projects/{project}/tasks` supports `status`, `priority`, `q`, `page` as query params and returns a paginator JSON.
  - `GET /api/tasks/{task}` loads `comments` and `reminders`.
- Comments:
  - `POST /api/tasks/{task}/comments` FormRequest expects `content` required (string). Controller expects `body` when creating comment — to avoid failure we send both keys.
- Reminders:
  - `POST /api/tasks/{task}/reminders` expects `runs_at` `required|date|after:now`. The collection computes `now_plus_1_minute` to satisfy `after:now`.

Common failure causes and debugging
- Missing/expired Bearer token:
  - Ensure `tokenA` or `tokenB` is set in environment (Login steps set these).
  - Authorization header must be `Bearer {{tokenA}}` (requests in collection include that).
- Wrong `base_url` or server not running:
  - Ensure `php artisan serve` is running on `127.0.0.1:8000`, or update `base_url` in environment.
- Validation errors:
  - Check request body matches the FormRequests. The collection uses the FormRequest fields.
  - For comments, remember we include both `content` and `body`.
- Queue-related issues:
  - If reminders don't mark as sent, ensure `QUEUE_CONNECTION=database` and `php artisan queue:work` is running.
  - Scheduler may be required if reminders are scheduled through tasks; `php artisan schedule:work` is recommended.
- Duplicate member add returns 409 (intended).

Manual checks after run
- After creating reminder and running queues/schedule, GET task should show the reminder's `sent_at` set (the collection cannot automatically wait and re-run; perform manually after ~70s).

Computing `now_plus_1_minute`
- The collection's Create Reminder request has a Pre-request script that computes and stores `now_plus_1_minute` automatically if not present.
- You can also set `now_plus_1_minute` manually in environment (ISO string format), for example: