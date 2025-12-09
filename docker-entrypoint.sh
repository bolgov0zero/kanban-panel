#!/bin/bash
set -e

# Запускаем cron от имени www-data
echo "Starting cron service..."
cron

# Создаем базу данных если не существует
if [ ! -f /var/www/html/db.sqlite ]; then
	echo "Creating database..."
	php /var/www/html/init_db.php > /dev/null 2>&1 || true
	mv /var/www/html/db.sqlite /var/www/html/db/ 2>/dev/null || true
fi

# Настраиваем права
chown -R www-data:www-data /var/www/html/ /var/log/cron.log

# Запускаем основной процесс
exec "$@"