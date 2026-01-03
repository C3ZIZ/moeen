<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ReminderController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

// Protected routes (Authed)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', fn (\Illuminate\Http\Request $r) => $r->user());

    // Workspaces
    Route::get('/workspaces',             [WorkspaceController::class, 'index']);
    Route::post('/workspaces',            [WorkspaceController::class, 'store']);
    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show']);
    Route::patch('/workspaces/{workspace}', [WorkspaceController::class, 'update']);
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy']);

    // Workspace members
    Route::post('/workspaces/{workspace}/members', [WorkspaceController::class, 'addMember']);
    Route::patch('/workspaces/{workspace}/members/{user}', [WorkspaceController::class, 'updateMemberRole']);
    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceController::class, 'removeMember']);

    // Projects (nested on create/list)
    Route::get('/workspaces/{workspace}/projects',  [ProjectController::class, 'index']);
    Route::post('/workspaces/{workspace}/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}',               [ProjectController::class, 'show']);
    Route::patch('/projects/{project}',             [ProjectController::class, 'update']);
    Route::delete('/projects/{project}',            [ProjectController::class, 'destroy']);

    // Tasks (nested on create/list)
    Route::get('/projects/{project}/tasks',  [TaskController::class, 'index']);
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}',              [TaskController::class, 'show']);
    Route::patch('/tasks/{task}',            [TaskController::class, 'update']);
    Route::delete('/tasks/{task}',           [TaskController::class, 'destroy']);

    // Comments / Reminders
    Route::post('/tasks/{task}/comments',  [CommentController::class, 'store']);
    Route::post('/tasks/{task}/reminders', [ReminderController::class, 'store']);
});
