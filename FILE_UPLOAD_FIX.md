# ファイルアップロードエラー修正方法

## 🔍 問題の原因

**エラー: Unexpected token '<', "<!DOCTYPE "... is not valid JSON**

このエラーは、JavaScriptが以下の処理をしていることが原因です：
```javascript
const data = await response.json();  // ← HTMLページをJSONとしてパースしようとしている
```

サーバーがエラーページ（HTML）を返しているのに、JavaScriptがJSON形式だと期待してパースしようとしています。

---

## ✅ 解決方法

### 1️⃣ ChatController.php を修正

**app/Http/Controllers/ChatController.php** を添付の `ChatController_fixed.php` で置き換えてください。

主な変更点：
- ファイルアップロード時のテキストファイル読み込みを強化
- エラーハンドリングの改善
- `messages` との関係をロード（`with(['messages.attachments'])`）

### 2️⃣ Blade ファイルの JavaScript を修正

**resources/views/chat.blade.php** の `handleFileUpload` 関数を以下のように修正：

```javascript
async function handleFileUpload(message, mode, conversationId, fileInput) {
    const loadingId = appendMessage('assistant', '考え中...', true);

    try {
        const formData = new FormData();
        formData.append('message', message);
        formData.append('mode', mode);
        if (conversationId) {
            formData.append('conversation_id', conversationId);
        }

        Array.from(fileInput.files).forEach(file => {
            formData.append('files[]', file);
        });

        const response = await fetch('{{ route("chat.send") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',  // ← これを追加
            },
            body: formData,
        });

        // ⭐ レスポンスチェックを追加
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server Error:', errorText);
            throw new Error(`HTTP ${response.status}: サーバーエラーが発生しました`);
        }

        // ⭐ Content-Typeチェックを追加
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON Response:', text);
            throw new Error('サーバーから正しい応答が返されませんでした');
        }

        const data = await response.json();
        document.getElementById(loadingId)?.remove();

        if (data.success) {
            appendMessage('assistant', data.response);

            if (data.conversation_id && !conversationIdInput.value) {
                conversationIdInput.value = data.conversation_id;
                window.history.replaceState({}, '', `/chat?conversation=${data.conversation_id}`);
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            appendMessage('error', `エラー: ${data.error || '不明なエラー'}`);
        }
    } catch (error) {
        document.getElementById(loadingId)?.remove();
        console.error('Upload Error:', error);
        appendMessage('error', `アップロードエラー: ${error.message}`);
    }
}
```

---

## 🛠️ デバッグ方法

### 1. ブラウザの開発者ツールで確認

1. **F12** を押して開発者ツールを開く
2. **Network** タブを開く
3. ファイルをアップロードして「解析して」を送信
4. 失敗したリクエストをクリック
5. **Response** タブで実際のレスポンスを確認

### 2. Laravel ログを確認

```bash
tail -f storage/logs/laravel.log
```

---

## 📋 チェックリスト

### サーバー側の確認
- [ ] `storage/app/public/attachments` ディレクトリが作成されている
- [ ] シンボリックリンクが作成されている: `php artisan storage:link`
- [ ] `attachments` テーブルが存在する
- [ ] `.env` に `ANTHROPIC_API_KEY` が設定されている
- [ ] ファイルサイズ制限が適切（php.ini: `upload_max_filesize`, `post_max_size`）

### ルーティングの確認
```bash
php artisan route:list | grep chat
```

以下のルートが存在することを確認：
```
POST   /chat/send        chat.send
POST   /chat/send-stream chat.send.stream
```

### データベースの確認
```sql
-- attachments テーブルが存在するか
SHOW TABLES LIKE 'attachments';

-- カラム構造の確認
DESCRIBE attachments;
```

---

## 🔧 よくあるエラーと対処法

### エラー 1: 「419 Page Expired」
**原因**: CSRFトークンが無効

**解決**:
```html
<!-- chat.blade.php の <head> に追加 -->
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### エラー 2: 「500 Internal Server Error」
**原因**: サーバー側のPHPエラー

**確認方法**:
```bash
tail -f storage/logs/laravel.log
```

### エラー 3: 「413 Payload Too Large」
**原因**: ファイルサイズが大きすぎる

**解決**: `php.ini` を編集
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

### エラー 4: 「404 Not Found」
**原因**: ルートが見つからない

**解決**:
```bash
php artisan route:clear
php artisan route:cache
```

---

## 🧪 動作テスト

### テスト用ファイルを作成
```bash
echo "<?php echo 'Hello World';" > test.php
```

### ブラウザでテスト
1. テストファイルをアップロード
2. 「このファイルを解析して」と入力
3. 送信ボタンをクリック
4. 開発者ツールのConsoleタブでエラーがないか確認

---

## 📞 まだエラーが出る場合

以下の情報を確認してください：

1. **ブラウザのコンソールエラー**（F12 → Console）
2. **Networkタブのレスポンス内容**（F12 → Network → 失敗したリクエスト → Response）
3. **Laravel ログ**（`storage/logs/laravel.log`）
4. **PHPエラーログ**（`/var/log/php-fpm/error.log` など）

これらの情報を共有していただければ、具体的な解決策を提案できます。
