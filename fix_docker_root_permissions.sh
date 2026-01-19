
#!/bin/bash

# カラー定義
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}=== Docker環境でのLaravel権限修正（root版） ===${NC}\n"

# docker-compose.ymlの存在確認
if [ ! -f "docker-compose.yml" ]; then
    echo -e "${RED}エラー: docker-compose.yml が見つかりません${NC}"
    exit 1
fi

# コンテナ名を検出
echo -e "${BLUE}1. コンテナを検出しています...${NC}"
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
    echo -e "${YELLOW}docker-compose up -d を実行してください${NC}"
    exit 1
fi

CONTAINER_NAME=$(docker ps --format "{{.Names}}" -f id=$CONTAINER_ID)
echo -e "${GREEN}✓ コンテナを検出: ${CONTAINER_NAME}${NC}\n"

# rootユーザーでコンテナ内の権限を修正
echo -e "${BLUE}2. rootユーザーでコンテナ内の権限を修正しています...${NC}"

docker exec -u root $CONTAINER_ID bash -c '
    cd /var/www/html
    
    # ディレクトリ作成
    echo "  - ディレクトリを作成中..."
    mkdir -p storage/framework/{cache/data,sessions,views}
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    
    # キャッシュ削除
    echo "  - 既存のキャッシュを削除中..."
    rm -rf storage/framework/cache/data/* 2>/dev/null
    rm -rf storage/framework/sessions/* 2>/dev/null
    rm -rf storage/framework/views/* 2>/dev/null
    rm -rf bootstrap/cache/*.php 2>/dev/null
    
    # 所有者変更
    echo "  - 所有者をwww-dataに変更中..."
    chown -R www-data:www-data storage
    chown -R www-data:www-data bootstrap/cache
    
    # 権限設定
    echo "  - 権限を775に設定中..."
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache
    
    echo "  ✓ 権限修正完了"
'

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ 権限修正が完了しました${NC}\n"
else
    echo -e "${RED}✗ 権限修正中にエラーが発生しました${NC}\n"
    exit 1
fi

# Laravelキャッシュクリア
echo -e "${BLUE}3. Laravelキャッシュをクリアしています...${NC}"

docker exec $CONTAINER_ID php artisan config:clear 2>&1 | grep -v "UnexpectedValueException" || true
docker exec $CONTAINER_ID php artisan cache:clear 2>&1 | grep -v "UnexpectedValueException" || true
docker exec $CONTAINER_ID php artisan view:clear 2>&1 | grep -v "UnexpectedValueException" || true
docker exec $CONTAINER_ID php artisan route:clear 2>&1 | grep -v "UnexpectedValueException" || true

echo -e "${GREEN}✓ キャッシュクリア完了${NC}\n"

# 権限確認
echo -e "${BLUE}4. 権限を確認しています...${NC}"
docker exec $CONTAINER_ID ls -la storage/ | head -5
echo ""
docker exec $CONTAINER_ID ls -la bootstrap/cache/ | head -5

echo -e "\n${GREEN}=== 完了 ===${NC}"
echo -e "${BLUE}次のステップ:${NC}"
echo "1. パスワードリセットファイルを作成："
echo "   ./create_sample_files.sh"
echo ""
echo "2. ブラウザでアクセス："
echo "   http://localhost:8000/forgot-password"
echo ""
echo -e "${BLUE}ログを確認する場合:${NC}"
echo "   docker exec -it $CONTAINER_NAME tail -f storage/logs/dev-ai-2026-01-19.log"
