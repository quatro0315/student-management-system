FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

WORKDIR /var/www/html
COPY . /var/www/html/

# Writable upload directories (Render filesystem is ephemeral unless a disk is mounted).
RUN mkdir -p /var/www/html/images/users /var/www/html/uploads/instructors \
    && chown -R www-data:www-data /var/www/html/images /var/www/html/uploads

# Prevent directory listings and expose a lightweight health endpoint.
RUN printf 'ServerName localhost\\n' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

EXPOSE 80
CMD ["apache2-foreground"]
