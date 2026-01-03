# Moeen API Testing Walkthrough

## Prerequisites

### 1. Environment Setup
Ensure your `.env` file has these critical settings:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moeen_db
DB_USERNAME=moeen_user
DB_PASSWORD=moeen_password

QUEUE_CONNECTION=database
MAIL_MAILER=log
```

### 2. Database Setup
```bash
# Create fresh database
php artisan migrate:fresh

# Verify tables exist
php artisan db:show
```

### 3. Install Sanctum (if not already installed)
```bash
php artisan install:api
```

### 4. Start Development Server
```bash
php artisan serve
# Server starts at http://127.0.0.1:8000
```

### 5. Start Queue Worker (REQUIRED for Reminders)
Open a **separate terminal** and run:
```bash
php artisan queue:work --queue=high,default,low
```
Keep this terminal running during tests.

### 6. Start Scheduler (OPTIONAL - for scheduled tasks)
Open **another terminal** if you want to test scheduled dispatching:
```bash
php artisan schedule:work
```

---

## Postman Setup

### Import Files
1. Open Postman
2. Import `postman/Moeen.postman_collection.json`
3. Import `postman/Moeen.local.postman_environment.json`
4. Select "Moeen Local" environment (top-right dropdown)

---

## Complete Testing Workflow

### Phase 1: Authentication

#### 1.1 Register User A (Alice - Owner)
- **Endpoint**: `POST /api/auth/register`
- **Expected**: 201 Created
- **Auto-saved**: `tokenA`, `userA_id`, `userA_email`
- **Body**:
  ```json
  {
    "name": "Alice Owner",
    "email": "alice@moeen.test",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```

#### 1.2 Register User B (Bob - Member)
- **Endpoint**: `POST /api/auth/register`
- **Expected**: 201 Created
- **Auto-saved**: `tokenB`, `userB_id`, `userB_email`
- **Body**:
  ```json
  {
    "name": "Bob Member",
    "email": "bob@moeen.test",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```

#### 1.3 Login User A
- **Endpoint**: `POST /api/auth/login`
- **Expected**: 200 OK
- **Auto-updates**: `tokenA`, `token` (active token)

#### 1.4 Get Current User
- **Endpoint**: `GET /api/me`
- **Expected**: 200 OK
- **Returns**: Current authenticated user details

#### 1.5 Logout
- **Endpoint**: `POST /api/auth/logout`
- **Expected**: 200 OK
- **Message**: "Logged out."

---

### Phase 2: Workspaces

#### 2.1 Create Workspace
- **Endpoint**: `POST /api/workspaces`
- **Expected**: 201 Created
- **Auto-saved**: `workspace_id`
- **Body**:
  ```json
  {
    "name": "Alice's Workspace"
  }
  ```
- **Notes**: 
  - Creator becomes owner
  - Owner is automatically added to `workspace_user` pivot table with role='owner'

#### 2.2 List Workspaces
- **Endpoint**: `GET /api/workspaces`
- **Expected**: 200 OK
- **Returns**: All workspaces where user is owner or member

#### 2.3 Show Workspace
- **Endpoint**: `GET /api/workspaces/{workspace_id}`
- **Expected**: 200 OK
- **Returns**: 
  - Workspace details
  - Owner information
  - Members list
  - Projects list

#### 2.4 Update Workspace
- **Endpoint**: `PATCH /api/workspaces/{workspace_id}`
- **Expected**: 200 OK
- **Authorization**: Admin or Owner only
- **Body**:
  ```json
  {
    "name": "Alice Updated Workspace"
  }
  ```

#### 2.5 Add Member (Bob)
- **Endpoint**: `POST /api/workspaces/{workspace_id}/members`
- **Expected**: 201 Created
- **Authorization**: Admin or Owner only
- **Body**:
  ```json
  {
    "email": "{{userB_email}}",
    "role": "member"
  }
  ```
- **Valid roles**: `admin`, `member` (owner cannot be set via this endpoint)

#### 2.6 Add Member - Duplicate (Negative Test)
- **Endpoint**: `POST /api/workspaces/{workspace_id}/members`
- **Expected**: **409 Conflict**
- **Message**: "User already a member."
- **Purpose**: Tests duplicate member prevention

#### 2.7 Update Member Role
- **Endpoint**: `PATCH /api/workspaces/{workspace_id}/members/{userB_id}`
- **Expected**: 200 OK
- **Authorization**: Admin or Owner only
- **Body**:
  ```json
  {
    "role": "admin"
  }
  ```
- **Restrictions**: Cannot change owner's role

#### 2.8 Remove Member
- **Endpoint**: `DELETE /api/workspaces/{workspace_id}/members/{userB_id}`
- **Expected**: 200 OK
- **Authorization**: Admin or Owner only
- **Restrictions**: Cannot remove owner

#### 2.9 Access Workspace as Non-Member (Negative Test)
- **Endpoint**: `GET /api/workspaces/{workspace_id}`
- **Pre-request**: Switches to `tokenB` (Bob is no longer a member)
- **Expected**: **403 Forbidden**
- **Message**: "Not a workspace member."
- **Post-test**: Restores `tokenA`

---

### Phase 3: Projects

#### 3.1 Create Project
- **Endpoint**: `POST /api/workspaces/{workspace_id}/projects`
- **Expected**: 201 Created
- **Auto-saved**: `project_id`
- **Authorization**: Admin or Owner only
- **Body**:
  ```json
  {
    "name": "MVP Launch"
  }
  ```

#### 3.2 List Projects
- **Endpoint**: `GET /api/workspaces/{workspace_id}/projects`
- **Expected**: 200 OK
- **Authorization**: Any workspace member
- **Returns**: All projects in workspace

#### 3.3 Show Project
- **Endpoint**: `GET /api/projects/{project_id}`
- **Expected**: 200 OK
- **Authorization**: Any workspace member
- **Returns**: Project details with `tasks_count`

#### 3.4 Update Project
- **Endpoint**: `PATCH /api/projects/{project_id}`
- **Expected**: 200 OK
- **Authorization**: Admin or Owner only
- **Body**:
  ```json
  {
    "name": "MVP Launch v2"
  }
  ```

#### 3.5 Delete Project
- **Endpoint**: `DELETE /api/projects/{project_id}`
- **Expected**: 200 OK
- **Authorization**: Admin or Owner only
- **Cascade**: Deletes all tasks, comments, and reminders

---

### Phase 4: Tasks

#### 4.1 Recreate Project
- Run this before testing tasks (since previous project was deleted)
- **Endpoint**: `POST /api/workspaces/{workspace_id}/projects`
- **Updates**: `project_id`

#### 4.2 Create Task - High Priority
- **Endpoint**: `POST /api/projects/{project_id}/tasks`
- **Expected**: 201 Created
- **Auto-saved**: `task_id`
- **Authorization**: Any workspace member
- **Body**:
  ```json
  {
    "title": "Setup CI/CD Pipeline",
    "description": "Configure GitHub Actions for automated testing and deployment",
    "status": "pending",
    "priority": "high",
    "due_at": "2026-01-15"
  }
  ```
- **Valid status values**: `pending`, `in_progress`, `completed`
- **Valid priority values**: `low`, `medium`, `high`

#### 4.3 Create Task - Medium Priority
- **Endpoint**: `POST /api/projects/{project_id}/tasks`
- **Expected**: 201 Created
- **Auto-saved**: `task2_id`
- **Body**:
  ```json
  {
    "title": "Write API Documentation",
    "description": "Document all REST endpoints with examples",
    "priority": "medium",
    "due_at": "2026-01-20"
  }
  ```

#### 4.4 List Tasks (Paginated)
- **Endpoint**: `GET /api/projects/{project_id}/tasks`
- **Expected**: 200 OK
- **Returns**: Paginated task list (20 per page)
- **Response structure**:
  ```json
  {
    "data": [...],
    "current_page": 1,
    "per_page": 20,
    "total": 2
  }
  ```

#### 4.5 List Tasks - Filter by Status
- **Endpoint**: `GET /api/projects/{project_id}/tasks?status=pending`
- **Expected**: 200 OK
- **Returns**: Only tasks with status='pending'

#### 4.6 List Tasks - Filter by Priority
- **Endpoint**: `GET /api/projects/{project_id}/tasks?priority=high`
- **Expected**: 200 OK
- **Returns**: Only high priority tasks

#### 4.7 List Tasks - Search by Title
- **Endpoint**: `GET /api/projects/{project_id}/tasks?q=CI`
- **Expected**: 200 OK
- **Returns**: Tasks where title contains "CI"

#### 4.8 Show Task
- **Endpoint**: `GET /api/tasks/{task_id}`
- **Expected**: 200 OK
- **Authorization**: Any workspace member
- **Returns**: 
  - Task details
  - Comments array with user info
  - Reminders array

#### 4.9 Update Task - Change Status to 'in_progress'
- **Endpoint**: `PATCH /api/tasks/{task_id}`
- **Expected**: 200 OK
- **Authorization**: Any workspace member
- **Body**:
  ```json
  {
    "status": "in_progress"
  }
  ```

#### 4.10 Update Task - Complete
- **Endpoint**: `PATCH /api/tasks/{task_id}`
- **Expected**: 200 OK
- **Body**:
  ```json
  {
    "status": "completed"
  }
  ```

#### 4.11 Delete Task
- **Endpoint**: `DELETE /api/tasks/{task2_id}`
- **Expected**: 200 OK
- **Authorization**: Admin or Owner only
- **Cascade**: Deletes comments and reminders

---

### Phase 5: Comments

#### 5.1 Add Comment
- **Endpoint**: `POST /api/tasks/{task_id}/comments`
- **Expected**: 201 Created
- **Auto-saved**: `comment_id`
- **Authorization**: Any workspace member
- **Body**:
  ```json
  {
    "content": "Started working on the pipeline setup. Will use GitHub Actions."
  }
  ```
- **Note**: Comment is linked to the authenticated user

---

### Phase 6: Reminders

#### 6.1 Create Reminder
- **Endpoint**: `POST /api/tasks/{task_id}/reminders`
- **Expected**: 201 Created
- **Auto-saved**: `reminder_id`
- **Authorization**: Any workspace member
- **Pre-request script**: Automatically calculates `now_plus_1_minute`
- **Body**:
  ```json
  {
    "runs_at": "{{now_plus_1_minute}}"
  }
  ```

#### 6.2 Verify Reminder Execution
After 1-2 minutes:

1. **Check Queue Worker Terminal**:
   - Should see job processing log
   - Example: `[YYYY-MM-DD HH:MM:SS] Processing: App\Jobs\SendReminderJob`

2. **Check Laravel Log**:
   ```bash
   tail -f storage/logs/laravel.log
   ```
   - Look for: `[YYYY-MM-DD HH:MM:SS] local.INFO: Reminder fired`
   - Contains: reminder_id, task_id, runs_at

3. **Query Database**:
   ```sql
   SELECT id, task_id, runs_at, sent_at 
   FROM reminders 
   WHERE id = {reminder_id};
   ```
   - `sent_at` should be populated after job executes

---

## Common Issues & Troubleshooting

### Issue: 401 Unauthorized
**Cause**: Token not set or expired  
**Solution**: 
- Re-run login request
- Check environment variable `token` is set
- Verify Bearer token in Authorization tab

### Issue: 403 Forbidden
**Cause**: Insufficient permissions  
**Solution**:
- Check workspace membership: `GET /api/workspaces/{workspace_id}`
- Verify role in pivot table (admin/owner required for some operations)

### Issue: 409 Conflict
**Cause**: Resource already exists (e.g., duplicate member)  
**Solution**: This is expected behavior for duplicate operations

### Issue: Reminder Not Firing
**Causes**:
1. Queue worker not running
2. `QUEUE_CONNECTION` not set to `database`
3. `runs_at` timestamp in the past

**Solutions**:
1. Start queue worker: `php artisan queue:work`
2. Check `.env`: `QUEUE_CONNECTION=database`
3. Use Postman pre-request script for automatic `now_plus_1_minute`

### Issue: Migration Errors
**Cause**: Old migrations with wrong enum values  
**Solution**:
```bash
php artisan migrate:fresh
```
**Note**: All fixes applied use correct enums:
- Status: `pending`, `in_progress`, `completed`
- Priority: `low`, `medium`, `high`
- Role: `owner`, `admin`, `member`

---

## Database Schema Reference

### Key Relationships
```
users (1) --< workspaces (owner_id)
users (N) >-< workspaces (workspace_user pivot) [role]
workspaces (1) --< projects
projects (1) --< tasks
tasks (1) --< comments [user_id]
tasks (1) --< reminders
```

### Pivot Table: workspace_user
```sql
workspace_id | user_id | role          | created_at | updated_at
-------------|---------|---------------|------------|------------
1            | 1       | owner         | ...        | ...
1            | 2       | admin/member  | ...        | ...
```

### Cascade Deletes
- Delete workspace → deletes projects → deletes tasks → deletes comments + reminders
- Delete user → removes from workspace_user pivot
- Delete task → deletes comments + reminders

---

## Queue & Scheduler Commands

### Queue Worker Options
```bash
# Basic worker
php artisan queue:work

# With priority queues
php artisan queue:work --queue=high,default,low

# Stop after processing one job
php artisan queue:work --once

# Monitor failed jobs
php artisan queue:failed
```

### Scheduler Commands
```bash
# Run scheduler continuously
php artisan schedule:work

# Test scheduled tasks
php artisan schedule:list

# Run scheduled tasks manually
php artisan schedule:run
```

### Verify Queue Tables
```bash
php artisan db:table jobs
php artisan db:table failed_jobs
```

---

## Expected HTTP Status Codes

| Operation | Success | Failure |
|-----------|---------|---------|
| Register/Login | 200/201 | 422 (validation) |
| Create Resource | 201 | 422 (validation), 403 (forbidden) |
| Read Resource | 200 | 404 (not found), 403 (forbidden) |
| Update Resource | 200 | 422 (validation), 403 (forbidden), 404 |
| Delete Resource | 200 | 403 (forbidden), 404 (not found) |
| Duplicate Member | - | 409 (conflict) |
| Non-member Access | - | 403 (forbidden) |

---

## Automated Test Execution

### Run All Requests in Order
1. Select "Moeen API" collection
2. Click "Run" button (top-right)
3. Select "Moeen Local" environment
4. Click "Run Moeen API"
5. All tests execute sequentially with automatic variable saving

### Monitor Progress
- Green checkmarks = passed tests
- Red X = failed tests
- Console tab shows detailed logs

---

## Final Notes

1. **Tokens are Bearer tokens**: Sanctum issues API tokens automatically
2. **Environment variables persist**: No need to manually copy IDs between requests
3. **Negative tests are included**: Verify proper error handling
4. **Queue must run**: Reminders require active queue worker
5. **Database reset**: `php artisan migrate:fresh` for clean state
6. **All enums are fixed**: Migrations and validations now match

---

## Support

For issues or questions:
1. Check `storage/logs/laravel.log`
2. Run `php artisan queue:failed` for failed jobs
3. Verify database state with `php artisan tinker`
4. Check migration files match validation rules

**Happy Testing! 🚀**
