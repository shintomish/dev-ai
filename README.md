# 開発支援AIチャットアプリ (DevAI)

Laravel + Claude APIを使った開発支援チャットアプリケーション

## 機能

- 🤖 Claude Sonnet 4 による技術サポート
- 💬 会話履歴の保存・管理
- 🔧 開発支援モード（Laravel/Linux/Git/VBA専門）
- 📚 学習支援モード（初心者向け）
- 🗂️ 過去の会話の検索・再開

## 機能

- ✅ Claude API統合（Sonnet 4）
- ✅ ストリーミングレスポンス
- ✅ ファイルアップロード対応（テキスト、コード、ログファイル等）
- ✅ 会話履歴管理
- ✅ お気に入り機能
- ✅ タグ管理
- ✅ エクスポート機能（Markdown, JSON, Text）
- ✅ コードシンタックスハイライト
- ✅ マークダウン表示

## 技術スタック

- **Backend**: Laravel 11
- **Frontend**: Tailwind CSS, Alpine.js
- **Database**: MySQL 8.0
- **API**: Anthropic Claude API
- **Infrastructure**: Docker, Docker Compose

## セットアップ

### 前提条件

- Docker Desktop
- Git
- Composer
- Node.js 18+

### インストール手順

1. **リポジトリをクローン**
```bash
git clone https://github.com/shintomish/dev-ai.git
cd dev-ai
```

2. **依存関係のインストール**
```bash
composer install
npm install
```

3. **環境変数の設定**
```bash
cp .env.example .env
```

`.env` を編集して以下を設定：
- `APP_KEY`: `php artisan key:generate` で生成
- `ANTHROPIC_API_KEY`: Anthropic APIキー

4. **Dockerコンテナの起動**
```bash
docker compose up -d
```

5. **データベースのセットアップ**
```bash
# マイグレーション実行
docker compose exec app php artisan migrate

# 初期データのインポート（オプション）
docker compose exec -T db mysql -u root -proot dev_ai < database/backups/dev_ai_20260101.sql
```

6. **アクセス**
```
http://localhost:8000/chat
```

## 開発

### コンテナの管理
```bash
# 起動
docker compose up -d

# 停止
docker compose down

# ログ確認
docker compose logs -f

# 再起動
docker compose restart
```

### データベース接続（DBeaver）
```
Host: 127.0.0.1
Port: 3307
Database: dev_ai
Username: devuser
Password: devpass
```

### キャッシュクリア
```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan view:clear
```

## ディレクトリ構造
```
dev-ai/
├── app/
│   ├── Http/Controllers/
│   │   ├── ChatController.php
│   │   └── ConversationController.php
│   └── Models/
│       ├── Conversation.php
│       ├── Message.php
│       ├── Attachment.php
│       └── Tag.php
├── database/
│   ├── migrations/
│   └── backups/
├── resources/
│   └── views/
│       └── chat.blade.php
├── docker/
│   ├── nginx/
│   └── php/
├── docker-compose.yml
└── Dockerfile
```

## 今後の予定

- [ ] 会話検索機能
- [ ] ダークモード
- [ ] マークダウンプレビュー改善
- [ ] 画像アップロード対応
- [ ] APIトークン使用量表示
- [ ] マルチユーザー対応
- [ ] API エンドポイント
- [ ] Web公開

---

## 🚀 API ドキュメント

このアプリケーションはRESTful APIを提供しており、外部アプリケーションから利用できます。

### ベースURL
```
http://localhost:8000/api
```

本番環境: `https://your-domain.com/api`

---

## 📋 認証

このAPIはLaravel Sanctumトークンベース認証を使用しています。

### 1. ユーザー登録
```bash
POST /api/register
```

**リクエストボディ:**
```json
{
  "name": "山田太郎",
  "email": "yamada@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

**レスポンス:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "山田太郎",
    "email": "yamada@example.com",
    "created_at": "2026-01-07T12:00:00.000000Z"
  },
  "token": "1|abc123def456...",
  "token_type": "Bearer"
}
```

---

### 2. ログイン
```bash
POST /api/login
```

**リクエストボディ:**
```json
{
  "email": "yamada@example.com",
  "password": "password"
}
```

**レスポンス:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "山田太郎",
    "email": "yamada@example.com"
  },
  "token": "1|abc123def456...",
  "token_type": "Bearer"
}
```

---

### 3. ログアウト
```bash
POST /api/logout
Authorization: Bearer {token}
```

**レスポンス:**
```json
{
  "success": true,
  "message": "ログアウトしました"
}
```

---

### 4. ユーザー情報取得
```bash
GET /api/user
Authorization: Bearer {token}
```

**レスポンス:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "山田太郎",
    "email": "yamada@example.com",
    "created_at": "2026-01-07T12:00:00.000000Z"
  }
}
```

---

## 💬 会話管理

### 1. 会話一覧取得
```bash
GET /api/conversations
Authorization: Bearer {token}
```

**レスポンス:**
```json
{
  "success": true,
  "conversations": [
    {
      "id": 1,
      "title": "Pythonの質問",
      "mode": "dev",
      "is_favorite": false,
      "tags": ["Python", "初心者"],
      "message_count": 5,
      "total_tokens": 1234,
      "cost_usd": 0.05,
      "cost_jpy": 7.5,
      "created_at": "2026-01-07T12:00:00.000000Z",
      "updated_at": "2026-01-07T12:30:00.000000Z"
    }
  ]
}
```

---

### 2. 会話作成
```bash
POST /api/conversations
Authorization: Bearer {token}
```

**リクエストボディ:**
```json
{
  "title": "新しい会話",
  "mode": "dev"
}
```

**モード:**
- `dev`: プログラミングアシスタント
- `study`: 学習アシスタント

**レスポンス:**
```json
{
  "success": true,
  "conversation": {
    "id": 2,
    "title": "新しい会話",
    "mode": "dev",
    "is_favorite": false,
    "created_at": "2026-01-07T12:00:00.000000Z"
  }
}
```

---

### 3. 会話詳細取得
```bash
GET /api/conversations/{id}
Authorization: Bearer {token}
```

**レスポンス:**
```json
{
  "success": true,
  "conversation": {
    "id": 1,
    "title": "Pythonの質問",
    "mode": "dev",
    "is_favorite": false,
    "tags": ["Python"],
    "message_count": 5,
    "total_tokens": 1234,
    "cost_usd": 0.05,
    "cost_jpy": 7.5,
    "created_at": "2026-01-07T12:00:00.000000Z",
    "updated_at": "2026-01-07T12:30:00.000000Z"
  }
}
```

---

### 4. 会話削除
```bash
DELETE /api/conversations/{id}
Authorization: Bearer {token}
```

**レスポンス:**
```json
{
  "success": true,
  "message": "会話を削除しました"
}
```

---

### 5. お気に入り切り替え
```bash
POST /api/conversations/{id}/favorite
Authorization: Bearer {token}
```

**レスポンス:**
```json
{
  "success": true,
  "is_favorite": true
}
```

---

### 6. タグ更新
```bash
PUT /api/conversations/{id}/tags
Authorization: Bearer {token}
```

**リクエストボディ:**
```json
{
  "tags": ["Python", "初心者", "デバッグ"]
}
```

**レスポンス:**
```json
{
  "success": true,
  "tags": ["Python", "初心者", "デバッグ"]
}
```

---

## 📨 メッセージ

### 1. メッセージ一覧取得
```bash
GET /api/conversations/{id}/messages
Authorization: Bearer {token}
```

**レスポンス:**
```json
{
  "success": true,
  "messages": [
    {
      "id": 1,
      "role": "user",
      "content": "Pythonで配列を反転する方法は？",
      "input_tokens": null,
      "output_tokens": null,
      "total_tokens": null,
      "created_at": "2026-01-07T12:00:00.000000Z"
    },
    {
      "id": 2,
      "role": "assistant",
      "content": "Pythonで配列を反転するには...",
      "input_tokens": 50,
      "output_tokens": 120,
      "total_tokens": 170,
      "created_at": "2026-01-07T12:00:05.000000Z"
    }
  ]
}
```

---

### 2. メッセージ送信（Claude API連携）
```bash
POST /api/conversations/{id}/messages
Authorization: Bearer {token}
```

**リクエストボディ:**
```json
{
  "message": "Pythonで配列を反転する方法は？"
}
```

**レスポンス:**
```json
{
  "success": true,
  "conversation_id": 1,
  "user_message": {
    "id": 3,
    "role": "user",
    "content": "Pythonで配列を反転する方法は？",
    "created_at": "2026-01-07T12:00:00.000000Z"
  },
  "assistant_message": {
    "id": 4,
    "role": "assistant",
    "content": "Pythonで配列を反転するには、以下の方法があります...",
    "created_at": "2026-01-07T12:00:05.000000Z"
  },
  "tokens": {
    "input": 50,
    "output": 120,
    "total": 170
  },
  "cost": {
    "usd": 0.0021,
    "jpy": 0.315
  }
}
```

---

## 📊 統計

### 1. 月間統計
```bash
GET /api/stats/monthly
Authorization: Bearer {token}
```

**レスポンス:**
```json
{
  "success": true,
  "stats": {
    "input_tokens": 5000,
    "output_tokens": 15000,
    "total_tokens": 20000,
    "message_count": 50,
    "cost_usd": 0.24,
    "cost_jpy": 36.0
  }
}
```

---

### 2. 詳細統計
```bash
GET /api/stats/detailed
Authorization: Bearer {token}
```

**レスポンス:**（月間統計と同じ形式）

---

## 🔒 エラーレスポンス

### 認証エラー（401 Unauthorized）
```json
{
  "message": "Unauthenticated."
}
```

### 権限エラー（403 Forbidden）
```json
{
  "success": false,
  "message": "この会話にアクセスする権限がありません"
}
```

### バリデーションエラー（422 Unprocessable Entity）
```json
{
  "message": "メールアドレスまたはパスワードが正しくありません。",
  "errors": {
    "email": ["メールアドレスまたはパスワードが正しくありません。"]
  }
}
```

### サーバーエラー（500 Internal Server Error）
```json
{
  "success": false,
  "message": "メッセージの送信に失敗しました",
  "error": "Claude API request failed: ..."
}
```

---

## 💡 使用例

### cURLでの使用例
```bash
# 1. ログイン
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}' \
  | jq -r '.token')

# 2. 会話作成
CONVERSATION_ID=$(curl -s -X POST http://localhost:8000/api/conversations \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"API テスト","mode":"dev"}' \
  | jq -r '.conversation.id')

# 3. メッセージ送信
curl -X POST http://localhost:8000/api/conversations/$CONVERSATION_ID/messages \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message":"こんにちは！"}' \
  | jq
```

---

### JavaScriptでの使用例
```javascript
// 1. ログイン
const loginResponse = await fetch('http://localhost:8000/api/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password'
  })
});
const { token } = await loginResponse.json();

// 2. 会話作成
const conversationResponse = await fetch('http://localhost:8000/api/conversations', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    title: 'APIテスト',
    mode: 'dev'
  })
});
const { conversation } = await conversationResponse.json();

// 3. メッセージ送信
const messageResponse = await fetch(`http://localhost:8000/api/conversations/${conversation.id}/messages`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    message: 'こんにちは！'
  })
});
const result = await messageResponse.json();
console.log(result);
```

---

### Pythonでの使用例
```python
import requests

# 1. ログイン
response = requests.post('http://localhost:8000/api/login', json={
    'email': 'user@example.com',
    'password': 'password'
})
token = response.json()['token']

# 2. 会話作成
headers = {'Authorization': f'Bearer {token}'}
response = requests.post('http://localhost:8000/api/conversations', 
    headers=headers,
    json={'title': 'APIテスト', 'mode': 'dev'}
)
conversation_id = response.json()['conversation']['id']

# 3. メッセージ送信
response = requests.post(
    f'http://localhost:8000/api/conversations/{conversation_id}/messages',
    headers=headers,
    json={'message': 'こんにちは！'}
)
print(response.json())
```

---

## 📦 レート制限

現在、レート制限は設定されていません。本番環境では適切なレート制限の設定を推奨します。

---

## 🔐 セキュリティ

- すべてのAPIエンドポイントは認証が必要です（`/register`と`/login`を除く）
- トークンは安全に保管してください
- HTTPSを使用することを強く推奨します
- トークンは定期的に再発行することを推奨します

---

## ライセンス

MIT License

## 作成者

shintomish