#!/bin/bash

# カラー定義
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}=== Laravel ロケール設定確認・変更ツール ===${NC}\n"

# プロジェクトルート確認
if [ ! -f "config/app.php" ]; then
    echo -e "${RED}エラー: config/app.php が見つかりません${NC}"
    exit 1
fi

# 現在の設定を確認
echo -e "${BLUE}1. 現在のロケール設定を確認しています...${NC}"
CURRENT_LOCALE=$(grep "'locale'" config/app.php | grep -v "//" | head -1 | sed "s/.*'locale'.*=>.*'\(.*\)'.*/\1/")
echo -e "現在のロケール設定: ${YELLOW}${CURRENT_LOCALE}${NC}\n"

if [ "$CURRENT_LOCALE" = "ja" ]; then
    echo -e "${GREEN}✓ ロケールは既にjaに設定されています${NC}\n"
else
    echo -e "${YELLOW}! ロケールがjaではありません${NC}"
    echo -e "${BLUE}2. ロケールをjaに変更しています...${NC}"
    
    # バックアップ作成
    cp config/app.php config/app.php.backup
    echo -e "  - バックアップを作成しました: config/app.php.backup"
    
    # localeをjaに変更
    sed -i "s/'locale' => 'en'/'locale' => 'ja'/g" config/app.php
    sed -i "s/'locale' => \"en\"/'locale' => 'ja'/g" config/app.php
    
    # 変更後の確認
    NEW_LOCALE=$(grep "'locale'" config/app.php | grep -v "//" | head -1 | sed "s/.*'locale'.*=>.*'\(.*\)'.*/\1/")
    echo -e "  - 新しいロケール設定: ${GREEN}${NEW_LOCALE}${NC}\n"
fi

# fallback_localeも確認
echo -e "${BLUE}3. fallback_locale設定を確認しています...${NC}"
FALLBACK_LOCALE=$(grep "'fallback_locale'" config/app.php | grep -v "//" | head -1 | sed "s/.*'fallback_locale'.*=>.*'\(.*\)'.*/\1/")
echo -e "fallback_locale設定: ${YELLOW}${FALLBACK_LOCALE}${NC}\n"

# 日本語ファイルの存在確認
echo -e "${BLUE}4. 日本語言語ファイルの存在を確認しています...${NC}"

check_file() {
    if [ -f "$1" ]; then
        echo -e "  ${GREEN}✓${NC} $1"
        return 0
    else
        echo -e "  ${RED}✗${NC} $1 ${YELLOW}(存在しません)${NC}"
        return 1
    fi
}

FILES_OK=true
check_file "lang/ja/auth.php" || FILES_OK=false
check_file "lang/ja/passwords.php" || FILES_OK=false
check_file "lang/ja/validation.php" || FILES_OK=false

echo ""

if [ "$FILES_OK" = false ]; then
    echo -e "${YELLOW}! 日本語ファイルが不足しています${NC}"
    echo -e "${BLUE}complete_japanese.sh を実行して日本語ファイルを作成してください${NC}\n"
fi

# Docker環境でキャッシュクリア
echo -e "${BLUE}5. キャッシュをクリアしています...${NC}"

if [ -f "docker-compose.yml" ]; then
    CONTAINER_ID=$(docker-compose ps -q app 2>/dev/null | head -1)
    if [ -n "$CONTAINER_ID" ]; then
        echo -e "  - config:clear"
        docker exec $CONTAINER_ID php artisan config:clear 2>&1 | tail -1
        echo -e "  - cache:clear"
        docker exec $CONTAINER_ID php artisan cache:clear 2>&1 | tail -1
        echo -e "  - view:clear"
        docker exec $CONTAINER_ID php artisan view:clear 2>&1 | tail -1
    fi
fi

echo -e "${GREEN}✓ キャッシュをクリアしました${NC}\n"

# 設定ファイルの該当箇所を表示
echo -e "${BLUE}6. config/app.php の設定内容:${NC}"
grep -A 2 "'locale'" config/app.php | grep -v "//"
echo ""

echo -e "${GREEN}=== 完了 ===${NC}"
echo -e "${BLUE}次のステップ:${NC}"

if [ "$CURRENT_LOCALE" != "ja" ]; then
    echo "1. コンテナを再起動："
    echo "   docker-compose restart"
    echo ""
fi

if [ "$FILES_OK" = false ]; then
    echo "2. 日本語ファイルを作成："
    echo "   ./complete_japanese.sh"
    echo ""
fi

echo "3. ブラウザで確認（強制リロード: Ctrl+F5）："
echo "   http://localhost:8000/forgot-password"
echo ""

# 設定内容の最終確認を表示
echo -e "${BLUE}現在の設定サマリー:${NC}"
echo "  locale: $CURRENT_LOCALE"
echo "  fallback_locale: $FALLBACK_LOCALE"
if [ "$FILES_OK" = true ]; then
    echo -e "  日本語ファイル: ${GREEN}OK${NC}"
else
    echo -e "  日本語ファイル: ${YELLOW}不足${NC}"
fi
