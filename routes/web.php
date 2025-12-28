<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('welcome');
});

// Route::post('/ai/chat', [AiController::class, 'chat']);

// チャット画面
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
Route::delete('/chat/conversation/{conversation}', [ChatController::class, 'destroy'])->name('chat.destroy');
Route::get('/chat/new', [ChatController::class, 'newConversation'])->name('chat.new');

// エクスポートルート
Route::get('/chat/conversation/{conversation}/export', [ChatController::class, 'export'])->name('chat.export');

