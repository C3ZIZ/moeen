<?php

namespace App\Http\Controllers\Api;

use App\Models\Workspace;
use App\Models\Project;
use App\Http\Requests\Api\ProjectStoreRequest;
use App\Http\Requests\Api\ProjectUpdateRequest;

class ProjectController extends BaseApiController
{
    public function index(Workspace $workspace)
    {
        $user = request()->user();
        $this->requireWorkspaceMember($user, $workspace);

        $projects = $workspace->projects()
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $projects]);
    }

    public function store(ProjectStoreRequest $request, Workspace $workspace)
    {
        $user = $request->user();
        $this->requireWorkspaceAdmin($user, $workspace);

        $project = $workspace->projects()->create($request->validated());

        return response()->json(['data' => $project], 201);
    }

    public function show(Project $project)
    {
        $user = request()->user();
        $workspace = $this->workspaceFromProject($project);
        $this->requireWorkspaceMember($user, $workspace);

        $project->loadCount('tasks');

        return response()->json(['data' => $project]);
    }

    public function update(ProjectUpdateRequest $request, Project $project)
    {
        $user = $request->user();
        $workspace = $this->workspaceFromProject($project);
        $this->requireWorkspaceAdmin($user, $workspace);

        $project->update($request->validated());

        return response()->json(['data' => $project]);
    }

    public function destroy(Project $project)
    {
        $user = request()->user();
        $workspace = $this->workspaceFromProject($project);
        $this->requireWorkspaceAdmin($user, $workspace);

        $project->delete();

        return response()->json(['message' => 'Project deleted.']);
    }
}
