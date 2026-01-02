<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// トップページ → チャットにリダイレクト
Route::get('/', function () {
    return redirect()->route('chat.index');
});

// ========== 会話検索 ==========
Route::get('/conversations/search', [ChatController::class, 'search'])->name('conversations.search');

// ========== 会話管理 ==========
Route::post('/conversations/{conversation}/favorite', [ChatController::class, 'toggleFavorite'])->name('conversations.favorite');
Route::delete('/conversations/{conversation}', [ChatController::class, 'destroy'])->name('conversations.destroy');
Route::put('/conversations/{conversation}/tags', [ChatController::class, 'updateTags'])->name('conversations.tags');
Route::get('/conversations/{conversation}/export', [ChatController::class, 'export'])->name('conversations.export');

// ========== チャット画面 ==========
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::get('/chat/new', [ChatController::class, 'new'])->name('chat.new');

// ========== メッセージ送信 ==========
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
Route::post('/chat/send-stream', [ChatController::class, 'sendStream'])->name('chat.send.stream');

// トークン使用統計（詳細）
Route::get('/stats/tokens/detailed', [ChatController::class, 'getDetailedStats'])->name('stats.tokens.detailed');


