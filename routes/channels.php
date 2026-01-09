<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// ユーザー専用チャンネル（プライベート）
Broadcast::channel('user.{userId}', function ($user, $userId) {
    // ログインユーザーが自分のチャンネルにのみアクセス可能
    return (int) $user->id === (int) $userId;
});

// 会話専用チャンネル（プライベート）
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // その会話の所有者のみアクセス可能
    $conversation = \App\Models\Conversation::find($conversationId);
    return $conversation && $conversation->user_id === $user->id;
});
