#!/bin/bash

# カラー定義
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}=== Laravel Breeze用 日本語翻訳ファイル作成 ===${NC}\n"

# lang/ja.json を作成
echo -e "${BLUE}1. lang/ja.json を作成しています...${NC}"

cat > lang/ja.json << 'EOF'
{
    "Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.": "パスワードをお忘れですか？問題ありません。メールアドレスを入力していただければ、新しいパスワードを設定できるリンクをメールでお送りします。",
    "Email": "メールアドレス",
    "Email Password Reset Link": "パスワードリセットリンクを送信",
    "Password": "パスワード",
    "Confirm Password": "パスワード（確認）",
    "Reset Password": "パスワードをリセット",
    "Log in": "ログイン",
    "Register": "登録",
    "Name": "名前",
    "Already registered?": "既にアカウントをお持ちですか？",
    "Forgot your password?": "パスワードをお忘れですか？",
    "Remember me": "ログイン状態を保持する",
    "Log Out": "ログアウト",
    "Dashboard": "ダッシュボード",
    "Profile": "プロフィール",
    "You're logged in!": "ログインしています！",
    "Verify Email Address": "メールアドレスを確認",
    "Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.": "ご登録ありがとうございます！開始する前に、お送りしたメールのリンクをクリックしてメールアドレスを確認してください。メールが届いていない場合は、再送信いたします。",
    "A new verification link has been sent to the email address you provided during registration.": "登録時に入力されたメールアドレスに新しい確認リンクを送信しました。",
    "Resend Verification Email": "確認メールを再送信",
    "Save": "保存",
    "Saved.": "保存しました。",
    "Profile Information": "プロフィール情報",
    "Update your account's profile information and email address.": "アカウントのプロフィール情報とメールアドレスを更新します。",
    "Update Password": "パスワードを更新",
    "Ensure your account is using a long, random password to stay secure.": "アカウントのセキュリティを保つため、長くランダムなパスワードを使用してください。",
    "Current Password": "現在のパスワード",
    "New Password": "新しいパスワード",
    "Confirm Password": "パスワード（確認）",
    "Delete Account": "アカウントを削除",
    "Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.": "アカウントを削除すると、すべてのリソースとデータが完全に削除されます。削除する前に、保持したいデータや情報をダウンロードしてください。",
    "Are you sure you want to delete your account?": "本当にアカウントを削除しますか？",
    "Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.": "アカウントを削除すると、すべてのリソースとデータが完全に削除されます。アカウントを完全に削除することを確認するため、パスワードを入力してください。",
    "Cancel": "キャンセル",
    "This is a secure area of the application. Please confirm your password before continuing.": "これはアプリケーションの安全な領域です。続行する前にパスワードを確認してください。",
    "Confirm": "確認",
    "Two Factor Authentication": "二段階認証",
    "Add additional security to your account using two factor authentication.": "二段階認証を使用してアカウントにセキュリティを追加します。",
    "Finish enabling two factor authentication.": "二段階認証の有効化を完了します。",
    "When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone's Google Authenticator application.": "二段階認証を有効にすると、認証時に安全でランダムなトークンの入力を求められます。このトークンは、お使いの携帯電話のGoogle Authenticatorアプリから取得できます。",
    "Two factor authentication is now enabled. Scan the following QR code using your phone's authenticator application.": "二段階認証が有効になりました。携帯電話の認証アプリを使用して、次のQRコードをスキャンしてください。",
    "Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.": "これらのリカバリーコードを安全なパスワードマネージャーに保存してください。二段階認証デバイスを紛失した場合、アカウントへのアクセスを回復するために使用できます。",
    "Enable": "有効化",
    "Regenerate Recovery Codes": "リカバリーコードを再生成",
    "Show Recovery Codes": "リカバリーコードを表示",
    "Disable": "無効化",
    "Browser Sessions": "ブラウザセッション",
    "Manage and log out your active sessions on other browsers and devices.": "他のブラウザやデバイスでのアクティブなセッションを管理してログアウトします。",
    "If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.": "必要に応じて、すべてのデバイスの他のすべてのブラウザセッションからログアウトできます。最近のセッションの一部を以下に示しますが、このリストは完全ではない場合があります。アカウントが侵害されたと思われる場合は、パスワードも更新してください。",
    "This device": "このデバイス",
    "Last active": "最終アクティブ",
    "Log Out Other Browser Sessions": "他のブラウザセッションをログアウト",
    "Done.": "完了しました。",
    "Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.": "すべてのデバイスの他のブラウザセッションからログアウトすることを確認するため、パスワードを入力してください。"
}
EOF

echo -e "${GREEN}✓ lang/ja.json を作成しました${NC}\n"

# resources/lang/ja.json にもコピー（Laravel 8以前との互換性）
if [ -d "resources/lang" ]; then
    echo -e "${BLUE}2. resources/lang/ja.json にもコピーしています...${NC}"
    cp lang/ja.json resources/lang/ja.json 2>/dev/null
    echo -e "${GREEN}✓ resources/lang/ja.json を作成しました${NC}\n"
fi

# config/app.php の確認
echo -e "${BLUE}3. config/app.php の設定を確認しています...${NC}"
if grep -q "'locale' => 'ja'" config/app.php; then
    echo -e "${GREEN}✓ ロケールは既にjaに設定されています${NC}\n"
else
    echo -e "${YELLOW}! config/app.php のlocaleをjaに変更します...${NC}"
    sed -i "s/'locale' => 'en'/'locale' => 'ja'/g" config/app.php
    sed -i "s/'locale' => \"en\"/'locale' => 'ja'/g" config/app.php
    echo -e "${GREEN}✓ ロケールをjaに変更しました${NC}\n"
fi

# キャッシュクリア
echo -e "${BLUE}4. キャッシュをクリアしています...${NC}"
if [ -f "docker-compose.yml" ]; then
    CONTAINER_ID=$(docker-compose ps -q app 2>/dev/null | head -1)
    if [ -n "$CONTAINER_ID" ]; then
        docker exec $CONTAINER_ID php artisan config:clear 2>&1 | tail -1
        docker exec $CONTAINER_ID php artisan cache:clear 2>&1 | tail -1
        docker exec $CONTAINER_ID php artisan view:clear 2>&1 | tail -1
        echo -e "${GREEN}✓ キャッシュをクリアしました${NC}\n"
    fi
fi

# ファイル確認
echo -e "${BLUE}5. 作成されたファイルを確認しています...${NC}"
if [ -f "lang/ja.json" ]; then
    echo -e "${GREEN}✓ lang/ja.json${NC}"
    echo "   $(wc -l < lang/ja.json) 行の翻訳を含んでいます"
fi
echo ""

echo -e "${GREEN}=== 完了 ===${NC}"
echo -e "${BLUE}次のステップ:${NC}"
echo "1. コンテナを再起動："
echo "   docker-compose restart"
echo ""
echo "2. ブラウザで確認（Ctrl+F5で強制リロード）："
echo "   http://localhost:8000/forgot-password"
echo ""
echo -e "${YELLOW}注意: ブラウザのキャッシュをクリアしてください${NC}"
