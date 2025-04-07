#!/bin/sh

# Aguardar o MySQL estar pronto
echo "Aguardando MySQL..."
while ! nc -z mysql 3306; do
  sleep 1
done

echo "MySQL está pronto."

# Executar migrações
php artisan migrate --force

# Iniciar o supervisor para processar filas
php artisan queue:work &

# Iniciar o PHP-FPM
exec php-fpm
