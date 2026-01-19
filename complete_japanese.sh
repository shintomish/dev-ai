#!/bin/bash

# カラー定義
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}=== Laravel完全日本語化スクリプト ===${NC}\n"

# プロジェクトルート確認
if [ ! -f "artisan" ]; then
    echo -e "${RED}エラー: Laravelプロジェクトのルートで実行してください${NC}"
    exit 1
fi

echo -e "${BLUE}1. 言語ファイルディレクトリを作成しています...${NC}"
mkdir -p lang/ja
mkdir -p resources/lang/ja

echo -e "${GREEN}✓ ディレクトリを作成しました${NC}\n"

# auth.php
echo -e "${BLUE}2. lang/ja/auth.php を作成中...${NC}"
cat > lang/ja/auth.php << 'EOF'
<?php

return [
    'failed' => 'メールアドレスまたはパスワードが正しくありません。',
    'password' => 'パスワードが正しくありません。',
    'throttle' => 'ログイン試行回数が多すぎます。:seconds秒後に再試行してください。',
];
EOF

# passwords.php
echo -e "${BLUE}3. lang/ja/passwords.php を作成中...${NC}"
cat > lang/ja/passwords.php << 'EOF'
<?php

return [
    'reset' => 'パスワードをリセットしました。',
    'sent' => 'パスワードリセット用のリンクをメールで送信しました。',
    'throttled' => 'しばらく待ってから再度お試しください。',
    'token' => 'このパスワードリセットトークンは無効です。',
    'user' => 'このメールアドレスに一致するユーザーが見つかりません。',
];
EOF

# validation.php
echo -e "${BLUE}4. lang/ja/validation.php を作成中...${NC}"
cat > lang/ja/validation.php << 'EOF'
<?php

return [
    'accepted' => ':attributeを承認してください。',
    'active_url' => ':attributeは有効なURLではありません。',
    'after' => ':attributeは:dateより後の日付にしてください。',
    'after_or_equal' => ':attributeは:date以降の日付にしてください。',
    'alpha' => ':attributeは英字のみで入力してください。',
    'alpha_dash' => ':attributeは英数字とハイフン、アンダースコアのみで入力してください。',
    'alpha_num' => ':attributeは英数字のみで入力してください。',
    'array' => ':attributeは配列形式で入力してください。',
    'before' => ':attributeは:dateより前の日付にしてください。',
    'before_or_equal' => ':attributeは:date以前の日付にしてください。',
    'between' => [
        'numeric' => ':attributeは:minから:maxまでの数値で入力してください。',
        'file' => ':attributeは:minKBから:maxKBまでのファイルサイズで入力してください。',
        'string' => ':attributeは:min文字から:max文字で入力してください。',
        'array' => ':attributeは:min個から:max個で入力してください。',
    ],
    'boolean' => ':attributeはtrueかfalseで入力してください。',
    'confirmed' => ':attributeが確認用の入力と一致しません。',
    'date' => ':attributeは有効な日付形式で入力してください。',
    'date_equals' => ':attributeは:dateと同じ日付にしてください。',
    'date_format' => ':attributeは:format形式で入力してください。',
    'different' => ':attributeと:otherは異なる値で入力してください。',
    'digits' => ':attributeは:digits桁で入力してください。',
    'digits_between' => ':attributeは:min桁から:max桁で入力してください。',
    'dimensions' => ':attributeの画像サイズが無効です。',
    'distinct' => ':attributeに重複した値があります。',
    'email' => ':attributeには有効なメールアドレスを入力してください。',
    'ends_with' => ':attributeは:valuesのいずれかで終わる必要があります。',
    'exists' => '選択された:attributeは存在しません。',
    'file' => ':attributeはファイル形式で入力してください。',
    'filled' => ':attributeは必須です。',
    'gt' => [
        'numeric' => ':attributeは:valueより大きい値で入力してください。',
        'file' => ':attributeは:valueKBより大きいファイルサイズで入力してください。',
        'string' => ':attributeは:value文字より多く入力してください。',
        'array' => ':attributeは:value個より多く入力してください。',
    ],
    'gte' => [
        'numeric' => ':attributeは:value以上の値で入力してください。',
        'file' => ':attributeは:valueKB以上のファイルサイズで入力してください。',
        'string' => ':attributeは:value文字以上で入力してください。',
        'array' => ':attributeは:value個以上で入力してください。',
    ],
    'image' => ':attributeは画像ファイル形式で入力してください。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeは:otherに存在しません。',
    'integer' => ':attributeは整数で入力してください。',
    'ip' => ':attributeは有効なIPアドレスで入力してください。',
    'ipv4' => ':attributeは有効なIPv4アドレスで入力してください。',
    'ipv6' => ':attributeは有効なIPv6アドレスで入力してください。',
    'json' => ':attributeは有効なJSON形式で入力してください。',
    'lt' => [
        'numeric' => ':attributeは:value未満の値で入力してください。',
        'file' => ':attributeは:valueKB未満のファイルサイズで入力してください。',
        'string' => ':attributeは:value文字未満で入力してください。',
        'array' => ':attributeは:value個未満で入力してください。',
    ],
    'lte' => [
        'numeric' => ':attributeは:value以下の値で入力してください。',
        'file' => ':attributeは:valueKB以下のファイルサイズで入力してください。',
        'string' => ':attributeは:value文字以下で入力してください。',
        'array' => ':attributeは:value個以下で入力してください。',
    ],
    'max' => [
        'numeric' => ':attributeは:max以下の値で入力してください。',
        'file' => ':attributeは:maxKB以下のファイルサイズで入力してください。',
        'string' => ':attributeは:max文字以下で入力してください。',
        'array' => ':attributeは:max個以下で入力してください。',
    ],
    'mimes' => ':attributeは:valuesタイプのファイル形式で入力してください。',
    'mimetypes' => ':attributeは:valuesタイプのファイル形式で入力してください。',
    'min' => [
        'numeric' => ':attributeは:min以上の値で入力してください。',
        'file' => ':attributeは:minKB以上のファイルサイズで入力してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
        'array' => ':attributeは:min個以上で入力してください。',
    ],
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeの形式が無効です。',
    'numeric' => ':attributeは数値で入力してください。',
    'password' => 'パスワードが正しくありません。',
    'present' => ':attributeが存在している必要があります。',
    'regex' => ':attributeの形式が無効です。',
    'required' => ':attributeは必須です。',
    'required_if' => ':otherが:valueの場合、:attributeは必須です。',
    'required_unless' => ':otherが:values以外の場合、:attributeは必須です。',
    'required_with' => ':valuesが存在する場合、:attributeは必須です。',
    'required_with_all' => ':valuesが全て存在する場合、:attributeは必須です。',
    'required_without' => ':valuesが存在しない場合、:attributeは必須です。',
    'required_without_all' => ':valuesが全て存在しない場合、:attributeは必須です。',
    'same' => ':attributeと:otherが一致しません。',
    'size' => [
        'numeric' => ':attributeは:sizeで入力してください。',
        'file' => ':attributeは:sizeKBで入力してください。',
        'string' => ':attributeは:size文字で入力してください。',
        'array' => ':attributeは:size個で入力してください。',
    ],
    'starts_with' => ':attributeは:valuesのいずれかで始まる必要があります。',
    'string' => ':attributeは文字列で入力してください。',
    'timezone' => ':attributeは有効なタイムゾーンで入力してください。',
    'unique' => ':attributeは既に使用されています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'url' => ':attributeは有効なURL形式で入力してください。',
    'uuid' => ':attributeは有効なUUID形式で入力してください。',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    'attributes' => [
        'name' => '名前',
        'username' => 'ユーザー名',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認）',
        'current_password' => '現在のパスワード',
        'new_password' => '新しいパスワード',
        'new_password_confirmation' => '新しいパスワード（確認）',
        'postal_code' => '郵便番号',
        'address' => '住所',
        'phone' => '電話番号',
        'mobile' => '携帯電話番号',
        'age' => '年齢',
        'sex' => '性別',
        'gender' => '性別',
        'birthday' => '誕生日',
        'year' => '年',
        'month' => '月',
        'day' => '日',
        'time' => '時間',
        'available' => '利用可能',
        'size' => 'サイズ',
        'title' => 'タイトル',
        'content' => '内容',
        'body' => '本文',
        'description' => '説明',
        'excerpt' => '抜粋',
        'date' => '日付',
        'subject' => '件名',
        'message' => 'メッセージ',
    ],
];
EOF

# pagination.php
echo -e "${BLUE}5. lang/ja/pagination.php を作成中...${NC}"
cat > lang/ja/pagination.php << 'EOF'
<?php

return [
    'previous' => '&laquo; 前へ',
    'next' => '次へ &raquo;',
];
EOF

# resources/lang/ja にもコピー（Laravel 8以前との互換性）
echo -e "${BLUE}6. resources/lang/ja にもコピーしています...${NC}"
cp -r lang/ja/* resources/lang/ja/ 2>/dev/null || true

echo -e "${GREEN}✓ 日本語ファイルを作成しました${NC}\n"

# config/app.php の確認
echo -e "${BLUE}7. config/app.php の設定を確認しています...${NC}"
if grep -q "locale.*=>.*'ja'" config/app.php; then
    echo -e "${GREEN}✓ ロケール設定は既にjaになっています${NC}\n"
else
    echo -e "${YELLOW}! config/app.php のロケール設定を手動で変更してください:${NC}"
    echo "'locale' => 'ja',"
    echo ""
fi

# キャッシュクリア
echo -e "${BLUE}8. キャッシュをクリアしています...${NC}"
if [ -f "docker-compose.yml" ]; then
    CONTAINER_ID=$(docker-compose ps -q app 2>/dev/null | head -1)
    if [ -n "$CONTAINER_ID" ]; then
        docker exec $CONTAINER_ID php artisan config:clear 2>&1 | tail -1
        docker exec $CONTAINER_ID php artisan cache:clear 2>&1 | tail -1
        docker exec $CONTAINER_ID php artisan view:clear 2>&1 | tail -1
    else
        php artisan config:clear
        php artisan cache:clear
        php artisan view:clear
    fi
else
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
fi

echo -e "${GREEN}✓ キャッシュをクリアしました${NC}\n"

echo -e "${GREEN}=== 完了 ===${NC}"
echo -e "${BLUE}次のステップ:${NC}"
echo "1. config/app.php を開いて、以下を確認してください："
echo "   'locale' => 'ja',"
echo "   'fallback_locale' => 'en',"
echo ""
echo "2. ブラウザでアクセスして確認："
echo "   http://localhost:8000/forgot-password"
echo ""
echo "3. もし英語のままの場合、以下を実行："
echo "   docker-compose restart"
