FROM php:8.2-apache

# Устанавливаем системные пакеты
RUN apt-get update && apt-get install -y \
    sqlite3 \
    cron \
    libsqlite3-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    openssl \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем расширения PHP
RUN docker-php-ext-install \
    pdo \
    pdo_sqlite \
    mbstring \
    xml \
    zip

# Включаем модули Apache
RUN a2enmod rewrite
RUN a2enmod ssl
RUN a2enmod headers

# Создаем директории
RUN mkdir -p /var/www/html/db
RUN mkdir -p /var/www/html/logs
RUN mkdir -p /etc/ssl/kanban

# Устанавливаем правильные права
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Генерируем самоподписанный SSL сертификат на 10 лет
RUN openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
    -keyout /etc/ssl/kanban/kanban-panel.key \
    -out /etc/ssl/kanban/kanban-panel.crt \
    -subj "/C=RU/ST=Moscow/L=Moscow/O=Kanban Panel/CN=kanban-panel" \
    && chmod 600 /etc/ssl/kanban/kanban-panel.key \
    && chmod 644 /etc/ssl/kanban/kanban-panel.crt

# Создаем конфигурацию Apache для HTTPS
RUN echo '<IfModule mod_ssl.c>
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
</IfModule>' > /etc/apache2/sites-available/kanban-ssl.conf

# Отключаем стандартные сайты и включаем наш
RUN a2dissite 000-default.conf
RUN a2dissite default-ssl.conf
RUN a2ensite kanban-ssl.conf

# Настраиваем порты для Apache
RUN echo 'Listen 80\nListen 443' > /etc/apache2/ports.conf

# Копируем ВСЕ файлы приложения
COPY www/ /var/www/html/
COPY version.json /var/www/html/

# Настраиваем cron (ПРОСТОЙ ВАРИАНТ)
RUN echo '* * * * * www-data cd /var/www/html && /usr/local/bin/php scheduled_kanban.php 2>&1' > /etc/cron.d/kanban
RUN chmod 0644 /etc/cron.d/kanban
RUN touch /var/log/cron.log
RUN chown www-data:www-data /var/log/cron.log

# Создаем entrypoint
RUN echo '#!/bin/bash
set -e

echo "=== Starting Kanban Panel ==="

# Запускаем cron
echo "Starting cron..."
cron

# Создаем лог файл для отладки cron
touch /var/www/html/logs/cron_debug.log
chown www-data:www-data /var/www/html/logs/cron_debug.log

echo "Starting Apache..."
exec apache2-foreground' > /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80 443

ENTRYPOINT ["docker-entrypoint.sh"]