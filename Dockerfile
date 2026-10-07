FROM php:8.3-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod headers \
    && sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf \
    && printf '%s\n' '<Directory /var/www/html/public>' '    AllowOverride All' '    Require all granted' '</Directory>' >> /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

COPY docker/render-start.sh /usr/local/bin/render-start
RUN chmod +x /usr/local/bin/render-start

EXPOSE 8080

CMD ["render-start"]
