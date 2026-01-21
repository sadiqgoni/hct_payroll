#!/bin/bash

# Deploy to cPanel Script
# This script helps prepare your Laravel app for cPanel deployment

set -e

echo "🚀 Preparing Laravel App for cPanel Deployment..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="hct"
EXCLUDE_FILE=".deployignore"

# Create exclude file if it doesn't exist
if [ ! -f "$EXCLUDE_FILE" ]; then
    cat > "$EXCLUDE_FILE" << 'EOF'
.git/
.git*
node_modules/
tests/
.env
.env.example
.editorconfig
.phpunit.result.cache
phpunit.xml
*.md
README*
DEPLOYMENT*
GUIDE*
HOW_TO*
PAYE_*
QUICK_START*
PROJECT_SUMMARY*
START_HERE*
debug-paye.sh
deploy.php
guide2
guideline.md
*.bak
.DS_Store
storage/logs/*
!storage/logs/.gitignore
bootstrap/cache/*
!bootstrap/cache/.gitignore
EOF
    echo -e "${GREEN}✓ Created .deployignore file${NC}"
fi

# Step 1: Clear local caches
echo -e "\n${YELLOW}Step 1: Clearing local caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

# Step 2: Optimize for production
echo -e "\n${YELLOW}Step 2: Optimizing for production...${NC}"
php artisan config:cache
# NOTE: route:cache will fail if there are duplicate route names (e.g., 'login').
# Keep this disabled unless route names are unique.
# php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Optimized${NC}"

# Step 3: Install production dependencies
echo -e "\n${YELLOW}Step 3: Installing production dependencies...${NC}"
composer install --optimize-autoloader --no-dev
echo -e "${GREEN}✓ Dependencies installed${NC}"

# Step 4: Create deployment package
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DEPLOY_DIR="deploy_$TIMESTAMP"
DEPLOY_ZIP="${PROJECT_NAME}_deploy_${TIMESTAMP}.zip"

echo -e "\n${YELLOW}Step 4: Creating deployment package...${NC}"

# Create temporary deployment directory
mkdir -p "$DEPLOY_DIR"

# Copy files excluding those in .deployignore
rsync -av \
    --exclude-from="$EXCLUDE_FILE" \
    --exclude="$DEPLOY_DIR" \
    --exclude="*.zip" \
    ./ "$DEPLOY_DIR/"

# Create the zip file
cd "$DEPLOY_DIR"
zip -r "../$DEPLOY_ZIP" . -q
cd ..

# Clean up temporary directory
rm -rf "$DEPLOY_DIR"

echo -e "${GREEN}✓ Deployment package created: $DEPLOY_ZIP${NC}"

# Step 5: Show deployment instructions
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}📦 DEPLOYMENT PACKAGE READY${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "\nPackage: ${YELLOW}$DEPLOY_ZIP${NC}"
echo -e "Size: $(du -h "$DEPLOY_ZIP" | cut -f1)"

echo -e "\n${YELLOW}Manual Deployment Steps:${NC}"
echo -e "1. Login to cPanel (kssbonline.org/cpanel)"
echo -e "2. Go to File Manager"
echo -e "3. Navigate to public_html/"
echo -e "4. ${RED}BACKUP CURRENT FILES FIRST!${NC}"
echo -e "   - Select all files"
echo -e "   - Click 'Compress'"
echo -e "   - Name it: backup_$(date +%Y%m%d).zip"
echo -e "5. Upload $DEPLOY_ZIP"
echo -e "6. Extract the zip file"
echo -e "7. Delete the zip file after extraction"
echo -e "8. Update .env file with production settings"
echo -e "9. Run these commands in cPanel Terminal:"
echo -e "   ${YELLOW}cd ~/public_html${NC}"
echo -e "   ${YELLOW}composer install --optimize-autoloader --no-dev${NC}"
echo -e "   ${YELLOW}php artisan migrate --force${NC}"
echo -e "   ${YELLOW}php artisan cache:clear${NC}"
echo -e "   ${YELLOW}php artisan config:cache${NC}"
echo -e "   ${YELLOW}# php artisan route:cache   (disabled: duplicate route names break route caching)${NC}"
echo -e "   ${YELLOW}php artisan view:cache${NC}"
echo -e "   ${YELLOW}chmod -R 775 storage bootstrap/cache${NC}"

echo -e "\n${GREEN}========================================${NC}"

# Reinstall all dependencies for local development
echo -e "\n${YELLOW}Restoring local development dependencies...${NC}"
composer install
php artisan config:clear
echo -e "${GREEN}✓ Local environment restored${NC}"

echo -e "\n${GREEN}✅ Done! Upload $DEPLOY_ZIP to your cPanel hosting.${NC}\n"
