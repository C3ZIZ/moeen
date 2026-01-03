# Moeen Postman Test Walkthrough

This guide explains how to import and run the provided Postman collection and environment for the Moeen Laravel API.

**Prerequisites**
- PHP, Composer and project dependencies installed.
- Database configured and migrated:
  - Run: `php artisan migrate`
  - Ensure database connection and `QUEUE_CONNECTION=database` if you plan to run queue workers.
  - If using database queues, ensure `jobs` and `failed_jobs` tables exist (migrations already included).
- Start the app server (local):
  - `php artisan serve --host=127.0.0.1 --port=8000`
  - Base URL expected: `http://127.0.0.1:8000`
- Ensure `.env` has `APP_URL=http://127.0.0.1:8000` (recommended).

Import files into Postman
1. Open Postman.
2. Import `postman/Moeen.postman_collection.json` (Collection).
3. Import `postman/Moeen.local.postman_environment.json` (Environment).
4. Select the imported environment from the top-right environment selector.

Environment variables (defaults included)
- `base_url` = http://127.0.0.1:8000
- `api_prefix` = /api
- `userA_email` / `userA_password`
- `userB_email` / `userB_password`
- Tokens and IDs will be populated by the collection tests.

How to run
1. In Postman, expand the `Moeen API` collection.
2. Recommended order: run folders sequentially as they are arranged:
   - Auth
   - Workspace (User A)
   - Workspace Membership + Role Rules
   - Projects (User A)
   - Tasks
   - Comments
   - Reminders + Queue Test
3. Use the Collection Runner:
   - Click the collection > Run (Runner).
   - Select the `Moeen Local` environment.
   - Run all requests in order.
   - If using multiple iterations, use 1 iteration.

Authorization handling
- Authorization is handled via environment variables `tokenA` and `tokenB`.
- The collection stores response tokens after Register/Login requests automatically using test scripts.

Pre-request scripts and time variables
- The "Create Reminder" request computes `now_plus_1_minute` automatically (if not already set) before sending. It saves the ISO timestamp to the environment.
- Alternatively you can set `now_plus_1_minute` manually in the environment to any ISO timestamp at least 1 minute in the future (the validator requires `runs_at` to be after `now`).

What the tests assert
- Positive and negative expectations are performed:
  - Authorization and membership enforcement (403 expected in specific flows).
  - Duplicate member addition returns 409.
  - Create endpoints return 201.
  - The collection saves `workspace_id`, `project_id`, `task_id`, `reminder_id`, `userA_id`, `userB_id` where available.

Important note — Controller / Request mismatch (Comments)
- While building tests I found a mismatch:
  - `app/Http/Requests/Api/CommentStoreRequest.php` validates a `content` field.
  - `app/Http/Controllers/Api/CommentController.php` uses `$request->validated()['body']` when creating a comment.
  - The `comments` table column is `content`.
- This mismatch will cause the "Add Comment" request to fail (server error) until the controller is patched to use `content`.
- Suggested fix (in `app/Http/Controllers/Api/CommentController.php`):
  Replace
  ```php
  $comment = $task->comments()->create([
      'user_id' => $user->id,
      'body' => $request->validated()['body'],
  ]);