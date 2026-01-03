<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Api\WorkspaceStoreRequest;
use App\Http\Requests\Api\WorkspaceUpdateRequest;
use App\Http\Requests\Api\AddMemberRequest;
use App\Http\Requests\Api\UpdateMemberRoleRequest;

class WorkspaceController extends BaseApiController
{
    public function index()
    {
        $user = request()->user();

        $workspaces = Workspace::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('users.id', $user->id))
            ->withCount('projects')
            ->orderByDesc('id')
            ->get()
            ->unique('id')
            ->values();

        return response()->json(['data' => $workspaces]);
    }

    public function store(WorkspaceStoreRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $workspace = DB::transaction(function () use ($user, $data) {
            $w = Workspace::create([
                'owner_id' => $user->id,
                'name' => $data['name'],
            ]);

            // keep pivot consistent: owner is also a member with role=owner
            $w->members()->attach($user->id, ['role' => 'owner']);

            return $w;
        });

        return response()->json(['data' => $workspace], 201);
    }

    public function show(Workspace $workspace)
    {
        $user = request()->user();
        $this->requireWorkspaceMember($user, $workspace);

        $workspace->load([
            'owner:id,name,email',
            'members:id,name,email',
            'projects:id,workspace_id,name',
        ]);

        return response()->json(['data' => $workspace]);
    }

    public function update(WorkspaceUpdateRequest $request, Workspace $workspace)
    {
        $user = $request->user();
        $this->requireWorkspaceAdmin($user, $workspace);

        $workspace->update($request->validated());

        return response()->json(['data' => $workspace]);
    }

    public function destroy(Workspace $workspace)
    {
        $user = request()->user();
        $this->requireWorkspaceOwner($user, $workspace);

        $workspace->delete();

        return response()->json(['message' => 'Workspace deleted.']);
    }

    public function addMember(AddMemberRequest $request, Workspace $workspace)
    {
        $user = $request->user();
        $this->requireWorkspaceAdmin($user, $workspace);

        $data = $request->validated();

        $newUser = User::where('email', strtolower($data['email']))->firstOrFail();
        $role = $data['role'] ?? 'member';

        // prevent duplicates
        $exists = $workspace->members()->whereKey($newUser->id)->exists();
        abort_if($exists, 409, 'User already a member.');

        $workspace->members()->attach($newUser->id, ['role' => $role]);

        return response()->json(['message' => 'Member added.'], 201);
    }

    public function updateMemberRole(UpdateMemberRoleRequest $request, Workspace $workspace, User $user)
    {
        $actor = $request->user();
        $this->requireWorkspaceAdmin($actor, $workspace);

        // cannot change owner role via this endpoint
        abort_if($workspace->owner_id === $user->id, 400, 'Cannot change owner role.');

        $isMember = $workspace->members()->whereKey($user->id)->exists();
        abort_unless($isMember, 404, 'User is not a member.');

        $workspace->members()->updateExistingPivot($user->id, [
            'role' => $request->validated()['role'],
        ]);

        return response()->json(['message' => 'Role updated.']);
    }

    public function removeMember(Workspace $workspace, User $user)
    {
        $actor = request()->user();
        $this->requireWorkspaceAdmin($actor, $workspace);

        // cannot remove owner
        abort_if($workspace->owner_id === $user->id, 400, 'Cannot remove owner.');

        $workspace->members()->detach($user->id);

        return response()->json(['message' => 'Member removed.']);
    }
}
