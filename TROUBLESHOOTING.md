# ファイルアップロード機能 - トラブルシューティングチェックリスト

## 🎯 実施手順

### ステップ1: ファイルを置き換える

```bash
# 1. ChatController.php を置き換え
cp ChatController_fixed.php app/Http/Controllers/ChatController.php

# 2. キャッシュをクリア
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### ステップ2: 必要なディレクトリとシンボリックリンク

```bash
# ストレージディレクトリを作成
mkdir -p storage/app/public/attachments

# シンボリックリンクを作成（まだの場合）
php artisan storage:link

# パーミッション設定
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### ステップ3: データベース確認

```bash
# attachmentsテーブルが存在するか確認
php artisan tinker
```

```php
// Tinker内で実行
Schema::hasTable('attachments'); // true が返ってくるべき
\App\Models\Attachment::count(); // エラーが出なければOK
exit;
```

### ステップ4: Blade ファイルの修正

**resources/views/chat.blade.php** を開き、以下の関数を置き換え：

1. `handleFileUpload` 関数
2. `handleNormalResponse` 関数  
3. `handleStreamingResponse` 関数

→ **chat_js_fixed.js** の内容をコピーして貼り付け

---

## 🔍 エラー診断フローチャート

### エラー: "Unexpected token '<', "<!DOCTYPE"..."

#### チェック項目：

**1. ルートが正しく登録されているか？**
```bash
php artisan route:list | grep chat.send
```
以下が表示されるべき：
```
POST   chat/send        chat.send
POST   chat/send-stream chat.send.stream
```

**2. ChatController が正しい名前空間にあるか？**
```bash
cat app/Http/Controllers/ChatController.php | head -3
```
以下が表示されるべき：
```php
<?php

namespace App\Http\Controllers;
```

**3. Laravelのエラーページが返ってきていないか？**

ブラウザの開発者ツール (F12) → Network タブで：
- リクエストURL: `/chat/send`
- ステータス: `200` (緑色) であるべき
- レスポンスタブを開いて内容を確認

もし `<!DOCTYPE html>` から始まるHTMLが返ってきていたら：
→ **サーバー側でエラーが発生している**

**対処法：Laravelログを確認**
```bash
tail -100 storage/logs/laravel.log
```

---

### エラー: "419 Page Expired"

**原因**: CSRFトークンの問題

**解決策:**

1. **chat.blade.php の <head> セクションに以下があるか確認**
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

2. **JavaScriptで正しく取得できているか確認**
```javascript
// chat.blade.php の <script> タグ内
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
console.log('CSRF Token:', csrfToken); // トークンが表示されるべき
```

3. **セッションが有効か確認**
```bash
# .env を確認
grep SESSION_ .env
```
以下のような設定があるべき：
```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

---

### エラー: "500 Internal Server Error"

**原因**: PHPのエラー

**確認方法:**

```bash
# Laravelログ
tail -f storage/logs/laravel.log

# PHPエラーログ（環境によって異なる）
tail -f /var/log/php-fpm/www-error.log
# または
tail -f /var/log/apache2/error.log
```

**よくあるエラー:**

#### 1. Attachmentモデルが見つからない
```
Class 'App\Models\Attachment' not found
```
→ `app/Models/Attachment.php` を作成

#### 2. attachmentsテーブルが存在しない
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'database.attachments' doesn't exist
```
→ マイグレーションを実行
```bash
php artisan migrate
```

#### 3. カラムが存在しない
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'content'
```
→ マイグレーションファイルを確認してカラムを追加

---

### エラー: "413 Payload Too Large"

**原因**: アップロードファイルのサイズ制限

**解決策:**

#### 1. PHP設定を変更
```bash
# php.ini の場所を確認
php --ini

# php.ini を編集
sudo nano /etc/php.ini  # または /etc/php/8.2/fpm/php.ini
```

以下の値を変更：
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

#### 2. Nginx設定（Nginxを使用している場合）
```bash
sudo nano /etc/nginx/nginx.conf
```

```nginx
http {
    client_max_body_size 10M;
}
```

#### 3. サービスを再起動
```bash
# PHP-FPM
sudo systemctl restart php-fpm

# Nginx
sudo systemctl restart nginx

# Apache
sudo systemctl restart apache2
```

---

## 🧪 動作確認テスト

### テスト1: 小さなテキストファイル

```bash
# テストファイル作成
echo "Hello, World!" > /tmp/test.txt
```

1. ブラウザでファイルをアップロード
2. 「このファイルの内容を教えて」と入力
3. 送信

**期待される結果：**
- AIが "Hello, World!" という内容を読み取って応答

### テスト2: コードファイル

```bash
# PHPファイル作成
cat > /tmp/test.php << 'EOF'
<?php
function greet($name) {
    return "Hello, " . $name;
}
echo greet("World");
EOF
```

1. ブラウザでファイルをアップロード
2. 「このコードを解説して」と入力
3. 送信

**期待される結果：**
- AIがコードの内容を解析して説明

### テスト3: 開発者ツールでの確認

**Chrome/Firefox: F12 → Console タブ**

以下のログが出力されるべき：
```
Uploading files: ["test.txt"]
Response status: 200
Response content-type: application/json; charset=UTF-8
Response data: {success: true, response: "...", conversation_id: 1}
```

エラーログが出ていたら、そのメッセージを確認。

---

## 🔧 Laravel Debugbar での詳細確認（推奨）

### インストール
```bash
composer require barryvdh/laravel-debugbar --dev
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

### 使い方
1. ブラウザでページをリロード
2. 画面下部にDebugbarが表示される
3. ファイルアップロードを実行
4. Debugbarの「Queries」タブでSQLクエリを確認
5. 「Exceptions」タブでエラーを確認

---

## 📊 確認コマンド一覧

```bash
# 1. ルート確認
php artisan route:list | grep chat

# 2. ストレージリンク確認
ls -la public/storage

# 3. パーミッション確認
ls -la storage/app/public

# 4. 設定確認
php artisan config:show filesystems

# 5. データベース接続確認
php artisan tinker
>> DB::connection()->getPdo();
>> exit

# 6. モデル確認
php artisan tinker
>> \App\Models\Attachment::first();
>> exit

# 7. ログ監視
tail -f storage/logs/laravel.log

# 8. キャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 💡 よくある間違い

### ❌ 間違い1: FormDataに Content-Type を指定
```javascript
// NG
const formData = new FormData();
fetch(url, {
    headers: {
        'Content-Type': 'multipart/form-data'  // ← これは不要
    },
    body: formData
});

// OK
const formData = new FormData();
fetch(url, {
    headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'  // ← これだけでOK
    },
    body: formData
});
```

### ❌ 間違い2: ファイル名のキーが間違っている
```javascript
// NG
formData.append('file', file);  // 単数形

// OK
formData.append('files[]', file);  // 配列形式
```

### ❌ 間違い3: バリデーションルールが厳しすぎる
```php
// NG
'files.*' => 'required|file|max:1024'  // 1MBは小さすぎる

// OK
'files.*' => 'nullable|file|max:5120'  // 5MB
```

---

## 📞 まだ解決しない場合

以下の情報をすべて確認してください：

### 1. ブラウザコンソール（F12 → Console）
スクリーンショットまたはエラーメッセージをコピー

### 2. Networkタブのレスポンス（F12 → Network）
- リクエストURL
- ステータスコード
- Response タブの内容（最初の100行）

### 3. Laravelログ
```bash
tail -100 storage/logs/laravel.log
```

### 4. 環境情報
```bash
php --version
php artisan --version
composer show | grep laravel
```

これらの情報があれば、具体的な解決策を提示できます！
