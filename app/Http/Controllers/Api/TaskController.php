<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\Task;
use App\Http\Requests\Api\TaskStoreRequest;
use App\Http\Requests\Api\TaskUpdateRequest;

class TaskController extends BaseApiController
{
    public function index(Project $project)
    {
        $user = request()->user();
        $workspace = $project->workspace()->firstOrFail();
        $this->requireWorkspaceMember($user, $workspace);

        $q = request('q');
        $status = request('status');
        $priority = request('priority');

        $tasks = $project->tasks()
            ->when($q, fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($priority, fn ($query) => $query->where('priority', $priority))
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($tasks);
    }

    public function store(TaskStoreRequest $request, Project $project)
    {
        $user = $request->user();
        $workspace = $project->workspace()->firstOrFail();
        $this->requireWorkspaceMember($user, $workspace); // members can create tasks

        $task = $project->tasks()->create($request->validated());

        return response()->json(['data' => $task], 201);
    }

    public function show(Task $task)
    {
        $user = request()->user();
        $workspace = $this->workspaceFromTask($task);
        $this->requireWorkspaceMember($user, $workspace);

        $task->load(['comments.user:id,name,email', 'reminders']);

        return response()->json(['data' => $task]);
    }

    public function update(TaskUpdateRequest $request, Task $task)
    {
        $user = $request->user();
        $workspace = $this->workspaceFromTask($task);
        $this->requireWorkspaceMember($user, $workspace); // members can update tasks

        $task->update($request->validated());

        return response()->json(['data' => $task]);
    }

    public function destroy(Task $task)
    {
        $user = request()->user();
        $workspace = $this->workspaceFromTask($task);
        $this->requireWorkspaceAdmin($user, $workspace); // only admin/owner can delete

        $task->delete();

        return response()->json(['message' => 'Task deleted.']);
    }
}
