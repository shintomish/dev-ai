#!/bin/bash

# カラー定義
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}=== メールテンプレート完全日本語化 ===${NC}\n"

# 1. 通知テンプレートを公開
echo -e "${BLUE}1. Laravel通知テンプレートを公開しています...${NC}"

if [ -f "docker-compose.yml" ]; then
    CONTAINER_ID=$(docker-compose ps -q app 2>/dev/null | head -1)
    if [ -n "$CONTAINER_ID" ]; then
        docker exec $CONTAINER_ID php artisan vendor:publish --tag=laravel-notifications
        echo -e "${GREEN}✓ 通知テンプレートを公開しました${NC}\n"
    fi
else
    php artisan vendor:publish --tag=laravel-notifications
    echo -e "${GREEN}✓ 通知テンプレートを公開しました${NC}\n"
fi

# 2. メールテンプレートの日本語化
echo -e "${BLUE}2. メールテンプレートを日本語化しています...${NC}"

# resources/views/vendor/notifications/email.blade.php を編集
cat > resources/views/vendor/notifications/email.blade.php << 'EOF'
<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('こんにちは')
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('よろしくお願いいたします。')<br>
{{ config('app.name') }}
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-mail::subcopy>
@lang(
    "「:actionText」ボタンをクリックできない場合は、以下のURLをコピーしてWebブラウザに貼り付けてください:",
    [
        'actionText' => $actionText,
    ]
) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-mail::subcopy>
@endisset
</x-mail::message>
EOF

echo -e "${GREEN}✓ メールテンプレートを日本語化しました${NC}\n"

# 3. ja.json に追加の翻訳を追加
echo -e "${BLUE}3. ja.json に追加の翻訳を追加しています...${NC}"

# 既存のja.jsonを読み込んで追加
if [ -f "lang/ja.json" ]; then
    # バックアップ
    cp lang/ja.json lang/ja.json.backup
    
    # 新しい翻訳を追加（既存のものとマージ）
    cat > lang/ja.json << 'EOF'
{
    "Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.": "パスワードをお忘れですか？問題ありません。メールアドレスを入力していただければ、新しいパスワードを設定できるリンクをメールでお送りします。",
    "Email": "メールアドレス",
    "Email Password Reset Link": "パスワードリセットリンクを送信",
    "Password": "パスワード",
    "Confirm Password": "パスワード（確認）",
    "Reset Password": "パスワードをリセット",
    "Forgot your password?": "パスワードをお忘れですか？",
    "Log in": "ログイン",
    "Register": "登録",
    "Name": "名前",
    "Whoops!": "エラーが発生しました！",
    "Hello!": "こんにちは",
    "Regards": "よろしくお願いいたします",
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:": "「:actionText」ボタンをクリックできない場合は、以下のURLをコピーしてWebブラウザに貼り付けてください:",
    "All rights reserved.": "All rights reserved."
}
EOF
    echo -e "${GREEN}✓ ja.json を更新しました${NC}\n"
fi

# 4. キャッシュクリア
echo -e "${BLUE}4. キャッシュをクリアしています...${NC}"
if [ -f "docker-compose.yml" ]; then
    CONTAINER_ID=$(docker-compose ps -q app 2>/dev/null | head -1)
    if [ -n "$CONTAINER_ID" ]; then
        docker exec $CONTAINER_ID php artisan config:clear 2>&1 | tail -1
        docker exec $CONTAINER_ID php artisan cache:clear 2>&1 | tail -1
        docker exec $CONTAINER_ID php artisan view:clear 2>&1 | tail -1
    fi
fi

echo -e "${GREEN}✓ キャッシュをクリアしました${NC}\n"

echo -e "${GREEN}=== 完了 ===${NC}"
echo -e "${BLUE}テスト方法:${NC}"
echo "1. http://localhost:8000/forgot-password にアクセス"
echo "2. メールアドレスを入力してリセットリンクを送信"
echo "3. 受信したメールを確認"
echo ""
echo -e "${YELLOW}期待される結果:${NC}"
echo "メールの最後の部分が以下のように日本語化されます："
echo "「パスワードをリセット」ボタンをクリックできない場合は、"
echo "以下のURLをコピーしてWebブラウザに貼り付けてください:"
