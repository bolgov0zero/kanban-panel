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
RUN mkdir -p /etc/ssl/kanban

# Генерируем самоподписанный SSL сертификат на 10 лет
RUN openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
    -keyout /etc/ssl/kanban/kanban-panel.key \
    -out /etc/ssl/kanban/kanban-panel.crt \
    -subj "/C=RU/ST=Moscow/L=Moscow/O=Kanban Panel/CN=kanban-panel/emailAddress=admin@kanban-panel.local" \
    -addext "subjectAltName = DNS:kanban-panel, DNS:localhost, IP:127.0.0.1" \
    && chmod 600 /etc/ssl/kanban/kanban-panel.key \
    && chmod 644 /etc/ssl/kanban/kanban-panel.crt

# Создаем конфигурацию Apache для HTTPS
RUN echo '<IfModule mod_ssl.c>\n\
<VirtualHost *:443>\n\
    ServerAdmin webmaster@localhost\n\
    ServerName kanban-panel\n\
    DocumentRoot /var/www/html\n\
    \n\
    # SSL конфигурация\n\
    SSLEngine on\n\
    SSLCertificateFile /etc/ssl/kanban/kanban-panel.crt\n\
    SSLCertificateKeyFile /etc/ssl/kanban/kanban-panel.key\n\
    \n\
    # Безопасность SSL\n\
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1\n\
    SSLCipherSuite HIGH:!aNULL:!MD5:!3DES\n\
    \n\
    <Directory /var/www/html>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    # Логирование\n\
    ErrorLog \${APACHE_LOG_DIR}/error.log\n\
    CustomLog \${APACHE_LOG_DIR}/access.log combined\n\
    \n\
    # Заголовки безопасности\n\
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains"\n\
    Header always set X-Frame-Options DENY\n\
    Header always set X-Content-Type-Options nosniff\n\
</VirtualHost>\n\
\n\
# Перенаправление HTTP -> HTTPS\n\
<VirtualHost *:80>\n\
    ServerName kanban-panel\n\
    DocumentRoot /var/www/html\n\
    \n\
    RewriteEngine On\n\
    RewriteCond %{HTTPS} off\n\
    RewriteRule ^(.*)$ https://%{HTTP_HOST}\$1 [R=301,L]\n\
    \n\
    <Directory /var/www/html>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>\n\
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

# Настраиваем права
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Настраиваем cron
RUN echo '* * * * * www-data cd /var/www/html && /usr/local/bin/php scheduled_kanban.php >> /var/log/cron.log 2>&1' > /etc/cron.d/kanban \
    && chmod 0644 /etc/cron.d/kanban \
    && touch /var/log/cron.log \
    && chown www-data:www-data /var/log/cron.log

# Создаем entrypoint для запуска cron и apache
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "=== Запуск Kanban Panel ===\n\
echo "SSL сертификат: /etc/ssl/kanban/kanban-panel.crt (10 лет)"\n\
echo "Доступ по HTTPS: https://localhost"\n\
echo "HTTP перенаправляется на HTTPS"\n\
\n\
echo "Starting cron..."\n\
cron\n\
\n\
echo "Checking database..."\n\
if [ ! -f /var/www/html/db.sqlite ]; then\n\
    echo "Initializing database..."\n\
    php /var/www/html/init_db.php > /dev/null 2>&1 || true\n\
fi\n\
\n\
echo "Starting Apache with SSL..."\n\
exec apache2-foreground\n' > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Пробрасываем порты 80 и 443
EXPOSE 80
EXPOSE 443

ENTRYPOINT ["docker-entrypoint.sh"]