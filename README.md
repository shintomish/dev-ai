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

## ライセンス

MIT License

## 作成者

shintomish