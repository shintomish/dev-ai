<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 認証不要のルート
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    // 認証関連
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // 会話関連
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy']);
    Route::post('/conversations/{conversation}/favorite', [ConversationController::class, 'toggleFavorite']);
    Route::put('/conversations/{conversation}/tags', [ConversationController::class, 'updateTags']);

    // メッセージ関連
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::post('/conversations/{conversation}/messages/stream', [MessageController::class, 'stream']); 

     // ファイルアップロード（NEW!）
    Route::post('/conversations/{conversation}/messages/upload', [MessageController::class, 'uploadWithFile']);
       
    // 統計関連
    Route::get('/stats/monthly', [ConversationController::class, 'monthlyStats']);
    Route::get('/stats/detailed', [ConversationController::class, 'detailedStats']);
});