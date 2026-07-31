FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev cron rsync logrotate \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install sockets pdo_sqlite \
    && docker-php-ext-enable sockets pdo_sqlite

WORKDIR /var/www/html

COPY src/ /opt/mikhmon/

RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite \
    && echo "ServerName mikhmon.local" >> /etc/apache2/apache2.conf

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

COPY crontab /etc/cron.d/mikhmon
RUN chmod 0644 /etc/cron.d/mikhmon

COPY logrotate.d /etc/logrotate.d/mikhmon
RUN chmod 0644 /etc/logrotate.d/mikhmon

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
