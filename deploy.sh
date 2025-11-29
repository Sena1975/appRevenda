#!/bin/bash
export COMPOSER_ALLOW_SUPERUSER=1

echo "🚀 Iniciando deploy: $(date)"

PROJECT_DIR="/var/www/appRevenda"
PHP_FPM_SERVICE="php8.3-fpm"
USER="www-data"

cd "$PROJECT_DIR" || { echo "❌ Pasta não encontrada: $PROJECT_DIR"; exit 1; }

# Resetando mudanças locais
# echo "🔁 Resetando alterações locais..."
# git reset --hard

# Atualizando código
# echo "📥 Puxando últimas alterações do GitHub..."
# git pull origin main

# Permissões
echo "🔒 Corrigindo permissões de pasta..."
chown -R $USER:$USER "$PROJECT_DIR"
find "$PROJECT_DIR" -type f -exec chmod 644 {} \;
find "$PROJECT_DIR" -type d -exec chmod 755 {} \;

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
./node_modules/.bin/vite build

# Reiniciando PHP-FPM
echo "♻️ Reiniciando PHP-FPM ($PHP_FPM_SERVICE)..."
systemctl restart "$PHP_FPM_SERVICE"

echo "✅ Deploy finalizado com sucesso em $(date)"

# git update-index --chmod=+x deploy.sh
# git commit -m "Marca deploy.sh como executável para deploy"
# git push origin main
