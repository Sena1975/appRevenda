#!/bin/bash
export COMPOSER_ALLOW_SUPERUSER=1

echo "🚀 Iniciando deploy: $(date)"

PROJECT_DIR="/var/www/appRevenda"
PHP_FPM_SERVICE="php8.3-fpm"
USER="www-data"

cd "$PROJECT_DIR" || { echo "❌ Pasta não encontrada: $PROJECT_DIR"; exit 1; }

# Atualizando código a partir do GitHub
echo "📥 Puxando últimas alterações do GitHub (main)..."
git fetch origin main
git reset --hard origin/main

# Permissões básicas
echo "🔒 Corrigindo permissões de pasta..."
chown -R $USER:$USER "$PROJECT_DIR"
find "$PROJECT_DIR" -type d -exec chmod 755 {} \;
find "$PROJECT_DIR" -type f -exec chmod 644 {} \;

# storage e cache precisam ter escrita para o PHP
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

# Instalando dependências do Laravel
echo "📦 Instalando dependências do PHP..."
composer install --no-dev --optimize-autoloader

# Limpando e cacheando configs Laravel
echo "🧹 Limpando e gerando cache Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrando banco de dados
echo "📂 Rodando migrations..."
php artisan migrate --force

# Build frontend com Vite
echo "🛠️ Compilando assets com Vite..."
npm install

# garantir que os binários do node_modules/.bin e do esbuild sejam executáveis
chmod +x node_modules/.bin/* 2>/dev/null || true
chmod +x node_modules/@esbuild/linux-x64/bin/* 2>/dev/null || true

npm run build

# Reiniciando PHP-FPM
echo "♻️ Reiniciando PHP-FPM ($PHP_FPM_SERVICE)..."
systemctl restart "$PHP_FPM_SERVICE"

echo "✅ Deploy finalizado com sucesso em $(date)"

# git update-index --chmod=+x deploy.sh
# git commit -m "Marca deploy.sh como executável para deploy"
# git push origin main
#.\deploy-local.ps1 "Mensagem do commit"
