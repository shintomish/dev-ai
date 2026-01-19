# Laravel パスワードリセット機能の日本語化とメール対応

## 1. 日本語言語ファイルのインストール

```bash
# Laravel 11の場合
php artisan lang:publish

# または手動で日本語ファイルを追加
```

## 2. 設定ファイル (config/app.php)

```php
// config/app.php
return [
    'locale' => 'ja',
    'fallback_locale' => 'en',
    'timezone' => 'Asia/Tokyo',
];
```

## 3. メール設定 (.env)

```env
# .env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

## 4. ルート設定 (routes/web.php)

```php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('password.request');

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('password.email');

Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');

Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
    ->name('password.update');
```

## 5. コントローラー

### app/Http/Controllers/Auth/ForgotPasswordController.php
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    protected function sendResetLinkResponse(Request $request, $response)
    {
        return back()->with('status', 'パスワードリセット用のリンクをメールで送信しました。');
    }

    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        return back()
            ->withErrors(['email' => 'このメールアドレスは登録されていません。']);
    }
}
```

### app/Http/Controllers/Auth/ResetPasswordController.php
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = '/home';

    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    protected function sendResetResponse(Request $request, $response)
    {
        return redirect($this->redirectPath())
            ->with('status', 'パスワードが正常に変更されました。');
    }

    protected function sendResetFailedResponse(Request $request, $response)
    {
        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'パスワードのリセットに失敗しました。もう一度お試しください。']);
    }
}
```

## 6. ビューファイル

### resources/views/auth/passwords/email.blade.php
```blade
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>パスワードリセット</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            text-align: center;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 13px;
            margin-top: 6px;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>パスワードをお忘れですか？</h1>
        <p class="subtitle">
            ご登録いただいているメールアドレスを入力してください。<br>
            パスワードリセット用のリンクをお送りします。
        </p>
        
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
        
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            
            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus
                    placeholder="example@email.com"
                >
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            
            <button type="submit">リセットリンクを送信</button>
        </form>
        
        <div class="back-link">
            <a href="{{ route('login') }}">← ログイン画面に戻る</a>
        </div>
    </div>
</body>
</html>
```

### resources/views/auth/passwords/reset.blade.php
```blade
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>パスワード再設定</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 13px;
            margin-top: 6px;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 6px;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>新しいパスワードを設定</h1>
        
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            
            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="{{ $email ?? old('email') }}" 
                    required 
                    autofocus
                >
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="password">新しいパスワード</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                >
                <div class="help-text">
                    8文字以上で、英数字を含めてください
                </div>
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="password_confirmation">新しいパスワード（確認）</label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    required
                >
            </div>
            
            <button type="submit">パスワードを変更</button>
        </form>
    </div>
</body>
</html>
```

## 7. メールテンプレートのカスタマイズ

```bash
# メール用のビューを公開
php artisan vendor:publish --tag=laravel-notifications
```

### resources/views/vendor/notifications/email.blade.php を編集

または、カスタム通知クラスを作成：

```bash
php artisan make:notification ResetPasswordNotification
```

### app/Notifications/ResetPasswordNotification.php
```php
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
```

### app/Models/User.php に追加
```php
use App\Notifications\ResetPasswordNotification;

public function sendPasswordResetNotification($token)
{
    $this->notify(new ResetPasswordNotification($token));
}
```

## 8. 日本語バリデーションメッセージ

### resources/lang/ja/validation.php
```php
<?php

return [
    'email' => ':attributeには有効なメールアドレスを指定してください。',
    'required' => ':attributeは必須です。',
    'min' => [
        'string' => ':attributeは:min文字以上で指定してください。',
    ],
    'confirmed' => ':attributeと確認用の入力が一致しません。',
    
    'attributes' => [
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認）',
    ],
];
```

### resources/lang/ja/passwords.php
```php
<?php

return [
    'reset' => 'パスワードをリセットしました。',
    'sent' => 'パスワードリセット用のリンクをメールで送信しました。',
    'throttled' => 'しばらく待ってから再度お試しください。',
    'token' => 'このパスワードリセットトークンは無効です。',
    'user' => 'このメールアドレスに一致するユーザーが見つかりません。',
];
```

## 9. Docker環境での設定

```yaml
# docker-compose.yml
version: '3'

services:
  app:
    environment:
      - MAIL_MAILER=smtp
      - MAIL_HOST=smtp.gmail.com
      - MAIL_PORT=587
      - MAIL_USERNAME=${MAIL_USERNAME}
      - MAIL_PASSWORD=${MAIL_PASSWORD}
      - MAIL_ENCRYPTION=tls
      - MAIL_FROM_ADDRESS=noreply@yourapp.com
```

## 10. 動作確認

1. キャッシュクリア
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

2. http://localhost:8000/forgot-password にアクセス

3. テスト（開発環境でログ出力）
```env
# .env
MAIL_MAILER=log
```

メールは `storage/logs/laravel.log` に出力されます。

これで完成です！
