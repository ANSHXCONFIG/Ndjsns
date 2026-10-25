FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libcurl4-openssl-dev libsqlite3-dev \
    && docker-php-ext-install curl pdo_sqlite \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/anshxosint-entrypoint
RUN chmod +x /usr/local/bin/anshxosint-entrypoint \
    && chown -R www-data:www-data /var/www/html/storage
ENV PORT=10000
EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/anshxosint-entrypoint"]
