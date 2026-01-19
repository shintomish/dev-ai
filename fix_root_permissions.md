# rootユーザーでDockerコンテナの権限を修正

## 問題の原因
コンテナ内でも一般ユーザー（"I have no name!"）で実行しているため、
www-dataが所有するファイルの権限を変更できません。

## 解決方法

### 方法1: rootユーザーでコンテナに入る（推奨）

```bash
# rootユーザーとしてコンテナに入る
docker-compose exec -u root app bash

# または
docker exec -u root -it $(docker-compose ps -q app) bash
```

### コンテナ内で実行するコマンド

```bash
# ディレクトリに移動
cd /var/www/html

# 必要なディレクトリを作成
mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# 既存のキャッシュを削除
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*

# 所有者をwww-dataに変更
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# 権限を設定
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# キャッシュクリア
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 確認
ls -la storage/
ls -la bootstrap/cache/

# コンテナから抜ける
exit
```

### 方法2: ホストから直接rootで実行

```bash
# ホスト（WSL）から直接実行
docker-compose exec -u root app chown -R www-data:www-data /var/www/html/storage
docker-compose exec -u root app chown -R www-data:www-data /var/www/html/bootstrap/cache
docker-compose exec -u root app chmod -R 775 /var/www/html/storage
docker-compose exec -u root app chmod -R 775 /var/www/html/bootstrap/cache

# キャッシュクリア（www-dataユーザーで実行）
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan route:clear
```

### 方法3: 一行で完結

```bash
docker-compose exec -u root app bash -c "
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && \
php artisan config:clear && \
php artisan cache:clear && \
php artisan view:clear && \
php artisan route:clear
"
```

## 完全な手順（コピペ用）

以下をWSLターミナルで順番に実行してください：

```bash
# 1. プロジェクトディレクトリに移動
cd ~/dev-ai

# 2. rootユーザーでコンテナに入る
docker-compose exec -u root app bash
```

コンテナ内で以下を実行：

```bash
# 3. 権限修正
cd /var/www/html
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 4. キャッシュクリア
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 5. 確認
ls -la storage/ | head -5
echo "✓ 権限修正完了"

# 6. コンテナから抜ける
exit
```

WSLに戻ったら：

```bash
# 7. パスワードリセットファイルを作成
./create_sample_files.sh

# 8. ブラウザでアクセス
echo "http://localhost:8000/forgot-password にアクセスしてください"
```

## トラブルシューティング

### docker-compose exec -u root が使えない場合

古いバージョンのdocker-composeでは `-u` オプションが使えない場合があります。

#### 解決方法1: docker コマンドを使う
```bash
# コンテナIDを取得
CONTAINER_ID=$(docker-compose ps -q app)

# rootユーザーで入る
docker exec -u root -it $CONTAINER_ID bash
```

#### 解決方法2: docker-composeをアップデート
```bash
# docker-composeのバージョン確認
docker-compose --version

# アップデート（必要に応じて）
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### "I have no name!" と表示される

これは正常です。コンテナ内のユーザー設定の問題ですが、
rootユーザー（-u root）で入れば権限は十分です。

### 権限変更後もエラーが出る

```bash
# キャッシュを完全にクリア
docker-compose exec -u root app rm -rf /var/www/html/storage/framework/cache/*
docker-compose exec -u root app rm -rf /var/www/html/storage/framework/sessions/*
docker-compose exec -u root app rm -rf /var/www/html/storage/framework/views/*
docker-compose exec -u root app rm -rf /var/www/html/bootstrap/cache/*.php

# コンテナを再起動
docker-compose restart
```

## 確認方法

権限が正しく設定されているか確認：

```bash
# ホストから確認
docker-compose exec app ls -la storage/ | head -10

# 正しい出力例：
# drwxrwxr-x  6 www-data www-data 4096 Jan 19 22:31 storage/
# drwxrwxr-x  3 www-data www-data 4096 Jan 19 22:31 framework/
# drwxrwxr-x  2 www-data www-data 4096 Jan 19 22:31 logs/
```

## 今後の予防策

### docker-compose.ymlを修正

今後同じ問題を避けるために、docker-compose.ymlに以下を追加：

```yaml
services:
  app:
    user: "1000:1000"  # あなたのUID:GIDに変更
    # または
    # user: "${UID:-1000}:${GID:-1000}"
```

### UID/GIDを確認

```bash
# あなたのUID/GIDを確認
id -u  # UID
id -g  # GID
```

### .envファイルに追加

```env
UID=1000
GID=1000
```

### コンテナを再ビルド

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

これで、コンテナ内で作成されるファイルが自動的にあなたの所有になります。
