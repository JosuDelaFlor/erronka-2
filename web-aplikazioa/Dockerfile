FROM php:8.2-apache

RUN docker-php-ext-install mysqli \
    && a2enmod headers

COPY docker/apache.conf /etc/apache2/conf-available/app-security.conf
RUN a2enconf app-security

COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && find /var/www/html -type d -exec chmod 0755 {} \; \
    && chmod 0750 /var/www/html/uploads

EXPOSE 80
