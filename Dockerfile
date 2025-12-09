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

# Копируем ВСЕ файлы приложения
COPY www/ /var/www/html/
COPY version.json /var/www/html/

# Копируем entrypoint скрипт
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Настраиваем cron
RUN echo '* * * * * www-data cd /var/www/html && /usr/local/bin/php scheduled_kanban.php >> /var/log/cron.log 2>&1' > /etc/cron.d/kanban \
    && chmod 0644 /etc/cron.d/kanban \
    && touch /var/log/cron.log \
    && chown www-data:www-data /var/log/cron.log

# Пробрасываем порты 80 и 443
EXPOSE 80
EXPOSE 443

# Используем entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]