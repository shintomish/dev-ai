<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PromptPresetController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 認証不要のルート
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ブロードキャスティング認証
Broadcast::routes(['middleware' => ['auth:sanctum']]);

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

     // ファイルアップロード
    Route::post('/conversations/{conversation}/messages/upload', [MessageController::class, 'uploadWithFile']);
       
    // 統計関連
    Route::get('/stats/monthly', [ConversationController::class, 'monthlyStats']);
    Route::get('/stats/detailed', [ConversationController::class, 'detailedStats']);

    // 検索関連
    Route::get('/search/conversations', [ConversationController::class, 'searchConversations']);
    Route::get('/search/messages', [MessageController::class, 'searchMessages']);
    Route::get('/search/all', [ConversationController::class, 'searchAll']);
    
    // プリセットプロンプト
    Route::get('/prompt-presets/{mode}', [PromptPresetController::class, 'index']);
    Route::get('/prompt-presets', [PromptPresetController::class, 'all']);

    // 統計API
    Route::get('/stats/tokens/detailed', [StatsController::class, 'detailed']);
    Route::get('/stats/tokens/by-mode', [StatsController::class, 'byMode']);
});