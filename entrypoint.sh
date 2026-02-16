#!/bin/sh
set -e

echo "🚀 Iniciando container de produção..."

# Garante que estamos no diretório da aplicação
cd /var/www

# 1. Executa as migrations
# O flag --force é necessário para rodar em produção sem confirmação interativa
echo "📦 Executando migrations..."
php artisan migrate --force

# 2. Otimização e Cache
# O comando 'optimize' gera cache de configuração e rotas.
# O 'view:cache' compila os templates Blade.
echo "🔥 Gerando caches de otimização..."
php artisan optimize
php artisan view:cache

# 3. Executa o comando principal do container (geralmente php-fpm)
echo "✅ Inicialização concluída. Iniciando serviço..."
exec "$@"