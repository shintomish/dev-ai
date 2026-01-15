<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');

    // プリセットプロンプト
    Route::get('/prompt-presets/{mode}', [ChatController::class, 'getPromptPresets']);              
});


// トップページ → チャットにリダイレクト
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('chat.index');
    }
    return redirect()->route('login');
});

// ========== 認証が必要なルート ==========
Route::middleware(['web', 'auth'])->group(function () {
    // ========== 会話検索 ==========
    Route::get('/conversations/search', [ChatController::class, 'search'])->name('conversations.search');

    // ========== 会話管理 ==========
    Route::post('/conversations/{conversation}/favorite', [ChatController::class, 'toggleFavorite'])->name('conversations.favorite');
    Route::delete('/conversations/{conversation}', [ChatController::class, 'destroy'])->name('conversations.destroy');
    Route::put('/conversations/{conversation}/tags', [ChatController::class, 'updateTags'])->name('conversations.tags');
    Route::get('/conversations/{conversation}/export', [ChatController::class, 'export'])->name('conversations.export');

    // トークン使用統計（詳細）
    Route::get('/stats/tokens/detailed', [ChatController::class, 'getDetailedStats'])->name('stats.tokens.detailed');

    // ========== チャット画面 ==========
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/new', [ChatController::class, 'new'])->name('chat.new');

    // ========== メッセージ送信 ==========
    Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
    Route::post('/chat/send-stream', [ChatController::class, 'sendStream'])->name('chat.send.stream');

    // ========== プリセットプロンプト ==========
    Route::get('/prompt-presets/{mode}', [ChatController::class, 'getPromptPresets']);
    
    // 統計
    Route::get('/stats/tokens/detailed', [ChatController::class, 'getDetailedStats']);
    Route::get('/stats/tokens/by-mode', [ChatController::class, 'getModeStats']);

});
// ========== ブロードキャスティング認証 ==========
Broadcast::routes(['middleware' => ['web', 'auth:sanctum']]);

require __DIR__.'/auth.php';