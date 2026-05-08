#!/usr/bin/env bash
set -euo pipefail

VPS_KEY="$HOME/.ssh/id_ed25519"
VPS_USER="root"
VPS_HOST="164.90.212.158"
VPS_PATH="/var/www/skilora"
SSH="ssh -i $VPS_KEY $VPS_USER@$VPS_HOST"

echo "=== Deploying to skilora.dev ==="

# 0. Bump static asset version in base template
DEPLOY_VER=$(date +%Y%m%d%H%M)
sed -i '' "s/?v=[0-9]*\"/?v=${DEPLOY_VER}\"/g" "$(dirname "$0")/templates/base.html.twig"
echo "Asset version: $DEPLOY_VER"

# 1. Rsync — exclude secrets, .env, vendor, var, uploads
rsync -avz --delete \
  --exclude='.git' \
  --exclude='var/' \
  --exclude='vendor/' \
  --exclude='node_modules/' \
  --exclude='.env' \
  --exclude='.env.local' \
  --exclude='.env.*.local' \
  --exclude='config/secrets/' \
  --exclude='public/uploads/' \
  --exclude='deploy.sh' \
  -e "ssh -i $VPS_KEY" \
  "$(dirname "$0")/" \
  "$VPS_USER@$VPS_HOST:$VPS_PATH/"

echo "=== Files synced ==="

# 2. Install deps + clear cache + fix perms + restart services
$SSH "cd $VPS_PATH && \
  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3 && \
  rm -rf var/cache/* && \
  mkdir -p var/cache var/log && \
  chown -R www-data:www-data var/ && \
  sudo -u www-data php bin/console cache:warmup --env=prod 2>&1 | tail -1 && \
  chmod 600 config/secrets/prod/prod.decrypt.private.php 2>/dev/null; \
  systemctl restart php8.3-fpm && \
  echo '=== Deploy complete ==='"
