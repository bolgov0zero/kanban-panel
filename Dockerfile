FROM php:8.2-apache

# Устанавливаем системные пакеты
RUN apt-get update && apt-get install -y \
    libssh2-1-dev \
    libssh2-1 \
    cron \
    sqlite3 \
    git \
    build-essential \
    autoconf \
    automake \
    libtool \
    libsqlite3-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
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

# Создаем директории
RUN mkdir -p /var/www/html/db

# Настраиваем права
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Копируем ВСЕ файлы приложения
COPY www/ /var/www/html/
COPY version.json /var/www/html/

# Копируем cron задание
COPY cronfile /etc/cron.d/kanban-cron
RUN chmod 0644 /etc/cron.d/kanban-cron \
    && crontab /etc/cron.d/kanban-cron

# Создаем лог файл для cron
RUN touch /var/log/cron.log

# Запускаем cron и apache
CMD ["sh", "-c", "cron && apache2-foreground"]
