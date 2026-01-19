<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('パスワードリセットのご案内')
            ->greeting('こんにちは')
            ->line('アカウントのパスワードリセットがリクエストされました。')
            ->action('パスワードをリセット', url(config('app.url').route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()], false)))
            ->line('このリンクは60分間有効です。')
            ->line('もしこのメールに心当たりがない場合は、無視していただいて構いません。')
            ->salutation('よろしくお願いいたします。');
    }
}

