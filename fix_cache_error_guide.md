# Laravelキャッシュエラーの解決方法

## エラー内容
```
UnexpectedValueException
The stream or file "storage/logs/laravel.log" could not be opened
```

このエラーは、Laravelのストレージディレクトリに書き込み権限がないために発生します。

## 即座に解決する方法

### 方法1: 自動修正スクリプトを使用（推奨）

```bash
# プロジェクトのルートディレクトリで実行
chmod +x fix_cache_permissions.sh
./fix_cache_permissions.sh
```

### 方法2: 手動で修正

#### ステップ1: 必要なディレクトリを作成
```bash
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
```

#### ステップ2: 既存のキャッシュを削除
```bash
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*
rm -rf bootstrap/cache/*.php
```

#### ステップ3: 権限を設定
```bash
# 775 権限を付与（読み取り、書き込み、実行）
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

#### ステップ4: 所有者を設定
```bash
# WSL環境の場合
sudo chown -R $USER:$USER storage
sudo chown -R $USER:$USER bootstrap/cache

# Dockerコンテナ内の場合
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

#### ステップ5: キャッシュをクリア
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

## WSL + Docker環境での特別な対応

### docker-compose.ymlの確認

```yaml
version: '3'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - .:/var/www/html
    environment:
      - WWWUSER=${WWWUSER:-1000}
      - WWWGROUP=${WWWGROUP:-1000}
```

### .envファイルに追加
```bash
WWWUSER=1000
WWWGROUP=1000
```

ユーザーIDとグループIDを確認：
```bash
id -u  # UID（例: 1000）
id -g  # GID（例: 1000）
```

### Dockerfileの調整

```dockerfile
FROM php:8.2-fpm

# ユーザーを作成
ARG WWWUSER=1000
ARG WWWGROUP=1000

RUN groupadd -g ${WWWGROUP} laravel && \
    useradd -u ${WWWUSER} -g laravel -m laravel

# 権限設定
RUN chown -R laravel:laravel /var/www/html

USER laravel
```

### コンテナを再ビルド
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## .gitignoreの確認

storageディレクトリが適切に除外されているか確認：

```gitignore
/storage/*.key
/storage/framework/cache/data/*
/storage/framework/sessions/*
/storage/framework/views/*
/storage/logs/*
!/storage/framework/cache/data/.gitignore
!/storage/framework/sessions/.gitignore
!/storage/framework/views/.gitignore
!/storage/logs/.gitignore
```

## トラブルシューティング

### エラーが続く場合

#### 1. SELinuxを確認（Fedora/RHEL/CentOS）
```bash
getenforce
# もしEnforcingの場合
sudo setenforce 0
```

または、適切なコンテキストを設定：
```bash
sudo chcon -R -t httpd_sys_rw_content_t storage
sudo chcon -R -t httpd_sys_rw_content_t bootstrap/cache
```

#### 2. ディレクトリの権限を詳しく確認
```bash
ls -la storage/
ls -la storage/framework/
ls -la bootstrap/
```

正しい権限の例：
```
drwxrwxr-x  storage/
drwxrwxr-x  storage/framework/
drwxrwxr-x  storage/framework/cache/
drwxrwxr-x  storage/framework/sessions/
drwxrwxr-x  storage/framework/views/
drwxrwxr-x  storage/logs/
drwxrwxr-x  bootstrap/cache/
```

#### 3. より強力な権限（最終手段）
```bash
# 777権限（すべてのユーザーに読み書き実行を許可）
chmod -R 777 storage
chmod -R 777 bootstrap/cache
```

⚠️ **注意**: 777権限は本番環境では推奨されません。開発環境でのみ使用してください。

#### 4. 完全なリセット
```bash
# すべてのキャッシュとログを削除
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*
rm -rf storage/logs/*
rm -rf bootstrap/cache/*

# .gitignoreファイルのみ残す
find storage -name ".gitignore" -o -name "*" -type f -delete

# 必要なディレクトリを再作成
mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# 権限を再設定
chmod -R 775 storage bootstrap/cache
chown -R $USER:$USER storage bootstrap/cache
```

## Docker環境でのコマンド実行

### コンテナ内で実行する場合
```bash
# コンテナに入る
docker-compose exec app bash

# または docker コマンド
docker exec -it dev-ai_app bash

# コンテナ内で権限修正
cd /var/www/html
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# キャッシュクリア
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### ホストから実行する場合
```bash
# docker-compose経由
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear

# 権限修正
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

## 予防策

### 1. プロジェクトのクローン後に必ず実行
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# 権限設定
chmod -R 775 storage bootstrap/cache
```

### 2. デプロイスクリプトに追加
```bash
# deploy.sh
#!/bin/bash
php artisan down
git pull origin main
composer install --no-dev
npm install
npm run build
php artisan migrate --force

# 権限修正
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

## 確認方法

すべての設定が完了したら：

```bash
# アプリケーションを起動
php artisan serve

# または Docker環境
docker-compose up -d

# ブラウザでアクセス
http://localhost:8000/forgot-password
```

ログを確認：
```bash
tail -f storage/logs/laravel.log
```

## まとめ

キャッシュエラーの主な原因：
1. ✗ storageディレクトリの権限不足
2. ✗ 所有者の不一致（Docker環境）
3. ✗ 必要なディレクトリの欠如

解決策：
1. ✓ 権限を775に設定
2. ✓ 適切な所有者に変更
3. ✓ 必要なディレクトリをすべて作成
4. ✓ キャッシュをクリア

これで問題なく動作するはずです！
