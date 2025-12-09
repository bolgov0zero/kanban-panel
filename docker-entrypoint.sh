#!/bin/bash
set -e

echo "=== Starting Kanban Panel ==="

# Создаем директорию для базы если её нет
mkdir -p /var/www/html/db
chown -R www-data:www-data /var/www/html/db
chmod 755 /var/www/html/db

# Запускаем cron
echo "Starting cron..."
cron

# Проверяем и создаем базу данных
echo "Checking database..."
if [ ! -f /var/www/html/db/db.sqlite ]; then
	echo "Creating database..."
	cd /var/www/html
	php init_db.php 2>&1 || echo "Database initialization completed"
	
	# Проверяем создание
	if [ -f /var/www/html/db/db.sqlite ]; then
		echo "✅ Database created successfully"
		chown www-data:www-data /var/www/html/db/db.sqlite
		chmod 666 /var/www/html/db/db.sqlite
	else
		echo "⚠️  Warning: Database file not created, but continuing..."
	fi
else
	echo "✅ Database already exists"
fi

# Настраиваем права на файлы
chown -R www-data:www-data /var/www/html/
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Настраиваем SSL конфигурацию
if [ ! -f /etc/apache2/sites-enabled/kanban-ssl.conf ]; then
	echo "Setting up Apache SSL configuration..."
	
	# Создаем конфигурацию
	cat > /etc/apache2/sites-available/kanban-ssl.conf << 'EOF'
<IfModule mod_ssl.c>
<VirtualHost *:443>
	ServerAdmin webmaster@localhost
	ServerName kanban-panel
	DocumentRoot /var/www/html
	
	# SSL конфигурация
	SSLEngine on
	SSLCertificateFile /etc/ssl/kanban/kanban-panel.crt
	SSLCertificateKeyFile /etc/ssl/kanban/kanban-panel.key
	
	# Безопасность SSL
	SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
	SSLCipherSuite HIGH:!aNULL:!MD5:!3DES
	
	<Directory /var/www/html>
		Options Indexes FollowSymLinks
		AllowOverride All
		Require all granted
	</Directory>
	
	# Логирование
	ErrorLog ${APACHE_LOG_DIR}/error.log
	CustomLog ${APACHE_LOG_DIR}/access.log combined
	
	# Заголовки безопасности
	Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains"
	Header always set X-Frame-Options DENY
	Header always set X-Content-Type-Options nosniff
</VirtualHost>

# Перенаправление HTTP -> HTTPS
<VirtualHost *:80>
	ServerName kanban-panel
	DocumentRoot /var/www/html
	
	RewriteEngine On
	RewriteCond %{HTTPS} off
	RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
	
	<Directory /var/www/html>
		Options Indexes FollowSymLinks
		AllowOverride All
		Require all granted
	</Directory>
</VirtualHost>
</IfModule>
EOF
	
	# Включаем конфигурацию
	a2dissite 000-default.conf 2>/dev/null || true
	a2dissite default-ssl.conf 2>/dev/null || true
	a2ensite kanban-ssl.conf
	
	# Настраиваем порты
	echo 'Listen 80' > /etc/apache2/ports.conf
	echo 'Listen 443' >> /etc/apache2/ports.conf
fi

echo "Starting Apache..."
exec apache2-foreground