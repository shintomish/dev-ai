#!/bin/bash

# カラー定義
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}=== Laravel権限完全修正（777版） ===${NC}\n"

# コンテナ検出
CONTAINER_ID=$(docker-compose ps -q app 2>/dev/null | head -1)

if [ -z "$CONTAINER_ID" ]; then
    for service in web php laravel; do
        CONTAINER_ID=$(docker-compose ps -q $service 2>/dev/null | head -1)
        if [ -n "$CONTAINER_ID" ]; then
            break
        fi
    done
fi

if [ -z "$CONTAINER_ID" ]; then
    echo -e "${RED}エラー: 実行中のコンテナが見つかりません${NC}"
    exit 1
fi

CONTAINER_NAME=$(docker ps --format "{{.Names}}" -f id=$CONTAINER_ID)
echo -e "${GREEN}✓ コンテナを検出: ${CONTAINER_NAME}${NC}\n"

echo -e "${BLUE}1. rootユーザーで完全な権限を設定しています...${NC}"
echo -e "${YELLOW}   (開発環境用に777権限を使用します)${NC}\n"

docker exec -u root $CONTAINER_ID bash -c '
    cd /var/www/html
    
    echo "  - 既存のキャッシュを完全削除中..."
    rm -rf storage/framework/cache/data/*
    rm -rf storage/framework/sessions/*
    rm -rf storage/framework/views/*
    rm -rf storage/logs/*.log
    rm -rf bootstrap/cache/*.php
    
    echo "  - ディレクトリを再作成中..."
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    
    echo "  - 所有者をwww-dataに変更中..."
    chown -R www-data:www-data storage
    chown -R www-data:www-data bootstrap/cache
    
    echo "  - 777権限を設定中（開発環境用）..."
    chmod -R 777 storage
    chmod -R 777 bootstrap/cache
    
    echo "  - .gitignoreファイルを再作成中..."
    touch storage/framework/cache/data/.gitignore
    touch storage/framework/sessions/.gitignore
    touch storage/framework/views/.gitignore
    touch storage/logs/.gitignore
    
    echo "*" > storage/framework/cache/data/.gitignore
    echo "!.gitignore" >> storage/framework/cache/data/.gitignore
    
    echo "*" > storage/framework/sessions/.gitignore
    echo "!.gitignore" >> storage/framework/sessions/.gitignore
    
    echo "*" > storage/framework/views/.gitignore
    echo "!.gitignore" >> storage/framework/views/.gitignore
    
    echo "*" > storage/logs/.gitignore
    echo "!.gitignore" >> storage/logs/.gitignore
    
    chown -R www-data:www-data storage
    chmod -R 777 storage
    
    echo "  ✓ 権限設定完了"
'

echo -e "${GREEN}✓ 権限修正が完了しました${NC}\n"

echo -e "${BLUE}2. Laravelキャッシュをクリアしています...${NC}"
docker exec $CONTAINER_ID php artisan config:clear 2>&1 | tail -1
docker exec $CONTAINER_ID php artisan cache:clear 2>&1 | tail -1
docker exec $CONTAINER_ID php artisan view:clear 2>&1 | tail -1
docker exec $CONTAINER_ID php artisan route:clear 2>&1 | tail -1

echo -e "${GREEN}✓ キャッシュクリア完了${NC}\n"

echo -e "${BLUE}3. 権限を確認しています...${NC}"
docker exec $CONTAINER_ID ls -la storage/framework/ | head -10
echo ""

echo -e "${GREEN}=== 完了 ===${NC}"
echo -e "${BLUE}次のコマンドでテストしてください:${NC}"
echo "curl http://localhost:8000/forgot-password"
echo ""
echo -e "${BLUE}または、ブラウザで:${NC}"
echo "http://localhost:8000/forgot-password"

