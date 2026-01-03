<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

abstract class BaseApiController extends Controller
{
    protected function workspaceRole(User $user, Workspace $workspace): ?string
    {
        if ($workspace->owner_id === $user->id) {
            return 'owner';
        }

        return $workspace->members()
            ->whereKey($user->id)
            ->value('role'); // admin|member|null
    }

    protected function requireWorkspaceMember(User $user, Workspace $workspace): string
    {
        $role = $this->workspaceRole($user, $workspace);
        abort_unless($role !== null, 403, 'Not a workspace member.');
        return $role;
    }

    protected function requireWorkspaceAdmin(User $user, Workspace $workspace): string
    {
        $role = $this->requireWorkspaceMember($user, $workspace);
        abort_unless(in_array($role, ['owner', 'admin'], true), 403, 'Requires admin role.');
        return $role;
    }

    protected function requireWorkspaceOwner(User $user, Workspace $workspace): void
    {
        abort_unless($workspace->owner_id === $user->id, 403, 'Requires owner role.');
    }

    protected function workspaceFromProject(Project $project): Workspace
    {
        return $project->workspace()->firstOrFail();
    }

    protected function workspaceFromTask(Task $task): Workspace
    {
        return $task->project()->firstOrFail()->workspace()->firstOrFail();
    }
}
