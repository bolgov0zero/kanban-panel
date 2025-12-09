FROM php:8.2-apache

# Устанавливаем необходимые пакеты
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем расширения PHP
RUN docker-php-ext-install pdo pdo_sqlite

# Включаем модуль rewrite
RUN a2enmod rewrite

# Отключаем SSL и HTTPS перенаправления
RUN a2dismod ssl
RUN a2dissite default-ssl

# Создаем простую конфигурацию
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    <Directory /var/www/html>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-kanban.conf

RUN a2dissite 000-default.conf
RUN a2ensite 000-kanban.conf

# Копируем файлы приложения
COPY www/ /var/www/html/

# Настраиваем права
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Создаем .htaccess без HTTPS перенаправлений
RUN echo 'Options +FollowSymLinks\n\
RewriteEngine On\n\
\n\
# Блокируем доступ к системным файлам\n\
<FilesMatch "\.(sqlite|db|ini|log|bak)$">\n\
    Order deny,allow\n\
    Deny from all\n\
</FilesMatch>' > /var/www/html/.htaccess

# Запускаем Apache
CMD ["apache2-foreground"]