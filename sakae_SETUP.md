# 栄町環境セットアップ手順

## 2025/01/05 に実行する手順

### 1. 環境確認
```bash
# 必要なツールの確認
git --version
docker --version
php --version
composer --version
node --version
```

### 2. リポジトリのクローン
```bash
cd ~
git clone https://github.com/shintomish/dev-ai.git
cd dev-ai
```

### 3. 依存関係のインストール
```bash
composer install
npm install
```

### 4. 環境変数の設定
```bash
cp .env.example .env
nano .env
```

**重要な設定項目:**
- `APP_KEY`: `php artisan key:generate` で生成
- `ANTHROPIC_API_KEY`: 中青木と同じAPIキー

### 5. Docker起動
```bash
docker compose up -d
sleep 10
```

### 6. データベースセットアップ
```bash
# マイグレーション
docker compose exec app php artisan migrate

# データをインポート
docker compose exec -T db mysql -u root -proot dev_ai < database/backups/dev_ai_20250101.sql

# 確認
docker compose exec app php artisan tinker --execute="
echo 'Conversations: ' . \App\Models\Conversation::count() . PHP_EOL;
echo 'Messages: ' . \App\Models\Message::count() . PHP_EOL;
"
```

### 7. 動作確認

ブラウザで `http://localhost:8000/chat` を開く

### 8. DBeaver設定
```
Connection name: DevAI (栄町)
Host: 127.0.0.1
Port: 3307
Database: dev_ai
Username: devuser
Password: devpass
```

## トラブルシューティング

### ポートが使用中
```bash
# 別のポートを使う場合
# docker-compose.yml を編集
nano docker-compose.yml

# webserver の ports を変更
ports:
  - "8080:80"  # 8000 → 8080

# その後
docker compose down
docker compose up -d
```

### データベース接続エラー
```bash
# コンテナのログを確認
docker compose logs db
docker compose logs app

# データベース再作成
docker compose exec db mysql -u root -proot -e "
DROP DATABASE IF EXISTS dev_ai;
CREATE DATABASE dev_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"
docker compose exec app php artisan migrate
```

## 日常的な同期フロー

### 中青木で作業終了時
```bash
git add .
git commit -m "feat: 新機能を追加"
git push
```

### 栄町で作業開始時
```bash
git pull
composer install  # composer.json が更新された場合のみ
docker compose exec app php artisan migrate  # 新しいマイグレーションがある場合
```
