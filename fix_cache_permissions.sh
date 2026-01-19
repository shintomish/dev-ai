#!/bin/bash

# カラー定義
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}=== Laravelキャッシュ権限修正スクリプト ===${NC}\n"

# プロジェクトルートの確認
if [ ! -f "artisan" ]; then
    echo -e "${RED}エラー: Laravelプロジェクトのルートディレクトリで実行してください${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Laravelプロジェクトを検出しました${NC}\n"

# 1. 必要なディレクトリを作成
echo -e "${BLUE}1. キャッシュディレクトリを作成しています...${NC}"
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

echo -e "${GREEN}✓ ディレクトリを作成しました${NC}\n"

# 2. 既存のキャッシュファイルを削除
echo -e "${BLUE}2. 既存のキャッシュファイルを削除しています...${NC}"
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*
rm -rf bootstrap/cache/*.php

echo -e "${GREEN}✓ キャッシュファイルを削除しました${NC}\n"

# 3. 権限を設定
echo -e "${BLUE}3. 権限を設定しています...${NC}"
chmod -R 775 storage
chmod -R 775 bootstrap/cache

echo -e "${GREEN}✓ 権限を設定しました${NC}\n"

# 4. 所有者を設定
echo -e "${BLUE}4. 所有者を設定しています...${NC}"
CURRENT_USER=$(whoami)
echo "現在のユーザー: $CURRENT_USER"

# WSL環境の場合
if grep -qi microsoft /proc/version 2>/dev/null; then
    echo -e "${YELLOW}WSL環境を検出しました${NC}"
    chown -R $USER:$USER storage
    chown -R $USER:$USER bootstrap/cache
    echo -e "${GREEN}✓ 所有者を $USER に設定しました${NC}"
# Dockerコンテナ内の場合
elif [ -f /.dockerenv ]; then
    echo -e "${YELLOW}Docker環境を検出しました${NC}"
    chown -R www-data:www-data storage
    chown -R www-data:www-data bootstrap/cache
    echo -e "${GREEN}✓ 所有者を www-data に設定しました${NC}"
else
    chown -R $USER:$USER storage
    chown -R $USER:$USER bootstrap/cache
    echo -e "${GREEN}✓ 所有者を $USER に設定しました${NC}"
fi

echo ""

# 5. ディレクトリ構造を確認
echo -e "${BLUE}5. ディレクトリ構造を確認しています...${NC}"
ls -la storage/framework/
ls -la bootstrap/cache/

echo ""

# 6. キャッシュをクリア
echo -e "${BLUE}6. Laravelキャッシュをクリアしています...${NC}"
php artisan config:clear 2>&1 || echo -e "${YELLOW}config:clear でエラーが発生しましたが続行します${NC}"
php artisan cache:clear 2>&1 || echo -e "${YELLOW}cache:clear でエラーが発生しましたが続行します${NC}"
php artisan view:clear 2>&1 || echo -e "${YELLOW}view:clear でエラーが発生しましたが続行します${NC}"
php artisan route:clear 2>&1 || echo -e "${YELLOW}route:clear でエラーが発生しましたが続行します${NC}"

echo ""
echo -e "${GREEN}=== 完了 ===${NC}"
echo -e "${BLUE}次のコマンドを実行してアプリケーションをテストしてください：${NC}"
echo "php artisan serve"
echo ""
echo -e "${BLUE}または、開発サーバーを再起動してください${NC}"
