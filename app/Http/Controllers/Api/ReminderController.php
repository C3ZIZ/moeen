<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Http\Requests\Api\ReminderStoreRequest;

class ReminderController extends BaseApiController
{
    public function store(ReminderStoreRequest $request, Task $task)
    {
        $user = $request->user();
        $workspace = $this->workspaceFromTask($task);
        $this->requireWorkspaceMember($user, $workspace);

        $reminder = $task->reminders()->create([
            'runs_at' => $request->validated()['runs_at'],
        ]);

        return response()->json(['data' => $reminder], 201);
    }
}
