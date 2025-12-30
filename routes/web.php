<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('welcome');
});

// Route::post('/ai/chat', [AiController::class, 'chat']);
Route::get('/', function () {
    return redirect()->route('chat.index');
});

// チャット画面
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
// Route::get('/chat/new', [ChatController::class, 'new'])->name('chat.new');
Route::get('/chat/new', function() {
    return redirect()->route('chat.index');
})->name('chat.new');
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
Route::post('/chat/send-stream', [ChatController::class, 'sendStream'])->name('chat.send.stream');

// 会話管理
Route::post('/chat/conversation/{conversation}/favorite', [ChatController::class, 'toggleFavorite']);
Route::delete('/chat/conversation/{conversation}', [ChatController::class, 'destroy']);

// エクスポートルート
Route::get('/chat/conversation/{conversation}/export', [ChatController::class, 'export'])->name('chat.export');

// お気に入り
Route::post('/chat/conversation/{conversation}/favorite', [ChatController::class, 'toggleFavorite'])->name('chat.favorite');

// タグ管理　追加・削除
Route::post('/chat/conversation/{conversation}/tag/attach', [ChatController::class, 'attachTag']);
Route::post('/chat/conversation/{conversation}/tag/detach', [ChatController::class, 'detachTag']);