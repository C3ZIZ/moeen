<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Http\Requests\Api\CommentStoreRequest;

class CommentController extends BaseApiController
{
    public function store(CommentStoreRequest $request, Task $task)
    {
        $user = $request->user();
        $workspace = $this->workspaceFromTask($task);
        $this->requireWorkspaceMember($user, $workspace);

        $comment = $task->comments()->create([
            'user_id' => $user->id,
            'content' => $request->validated()['content'],
        ]);

        return response()->json(['data' => $comment], 201);
    }
}
