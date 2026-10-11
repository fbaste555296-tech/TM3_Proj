FROM richarvey/nginx-php-fpm:latest

# Copy application files to server web root
COPY . /var/www/html

# Override default Nginx config with custom rewrite rules
COPY conf/nginx/site.conf /etc/nginx/sites-available/default.conf

ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1

# Install dependencies and fix permissions for Laravel
RUN composer install --no-dev --prefer-dist --optimize-autoloader && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache