<?php

use App\Http\Controllers\Api\NoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Note API routes
Route::apiResource('notes', NoteController::class);

// Additional note action routes
Route::patch('notes/{note}/toggle', [NoteController::class, 'toggle']);
Route::patch('notes/{note}/mark-done', [NoteController::class, 'markDone']);
Route::patch('notes/{note}/mark-undone', [NoteController::class, 'markUndone']);
