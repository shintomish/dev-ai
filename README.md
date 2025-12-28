# 開発支援AI

Laravel + Claude API を使った開発者向けAIチャットアプリケーション

## 機能

- 🤖 Claude Sonnet 4 による技術サポート
- 💬 会話履歴の保存・管理
- 🔧 開発支援モード（Laravel/Linux/Git/VBA専門）
- 📚 学習支援モード（初心者向け）
- 🗂️ 過去の会話の検索・再開

## 技術スタック

- **Backend**: Laravel 12, PHP 8.2
- **Database**: MariaDB 11
- **AI**: Claude API (Anthropic)
- **Frontend**: Blade + Tailwind CSS
- **Infrastructure**: Docker Compose

## セットアップ

### 1. リポジトリクローン
```bash
git clone https://github.com/shintomish/dev-ai.git
cd dev-ai
```

### 2. 環境変数設定
```bash
cp .env.example .env
```

`.env` を編集して以下を設定：
```ini
ANTHROPIC_API_KEY=sk-ant-xxxxx
CLAUDE_MODEL=claude-sonnet-4-20250514

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=dev_ai
DB_USERNAME=devuser
DB_PASSWORD=devpass
```

### 3. Docker起動
```bash
docker compose up -d
```

### 4. マイグレーション実行
```bash
docker compose exec app php artisan migrate
```

### 5. アクセス
```
http://localhost:8038/chat
```

## 使い方

### モード切替

- **開発支援モード**: Laravel/Linux/Git/VBA の技術相談
- **学習支援モード**: プログラミング初心者向けの丁寧な説明

### 便利なコマンド
```bash
# キャッシュクリア
docker compose exec app php artisan cache:clear

# マイグレーションリセット
docker compose exec app php artisan migrate:fresh

# ログ確認
docker compose logs -f app

# Tinker（DBデバッグ）
docker compose exec app php artisan tinker
```

## データベース

### DBeaver で接続
```
Host:       127.0.0.1
Port:       4306
Database:   dev_ai
Username:   root
Password:   root
```

### テーブル構成

- `conversations`: 会話セッション
- `messages`: メッセージ履歴（user/assistant）

## 開発

### エイリアス設定（推奨）

`~/.bashrc` に追加：
```bash
alias dart='docker compose exec app php artisan'
alias dcomposer='docker compose exec app composer'
alias dtinker='docker compose exec app php artisan tinker'
alias dlogs='docker compose logs -f app'
```

反映：
```bash
source ~/.bashrc
```

使用例：
```bash
dart migrate
dart cache:clear
dtinker
```

## トラブルシューティング

### Permission denied エラー
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:www-data storage bootstrap/cache
docker compose restart
```

### DB接続エラー
```bash
# コンテナ再起動
docker compose down
docker compose up -d

# 接続確認
docker compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

## ライセンス

Private（個人利用）

## 作成者

[@shintomish](https://github.com/shintomish)
