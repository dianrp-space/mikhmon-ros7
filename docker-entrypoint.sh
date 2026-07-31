#!/bin/sh
set -e

# Sync kode dari image ke /var/www/html (volume),
# tapi PERTAHANKAN file runtime: config.php, lang, theme, quickbt, logo, template voucher.
rsync -a --delete \
    --exclude 'include/config.php' \
    --exclude 'include/lang.php' \
    --exclude 'include/theme.php' \
    --exclude 'include/quickbt.php' \
    --exclude 'img/' \
    --exclude 'voucher/*.php' \
    --exclude 'data/' \
    /opt/mikhmon/ /var/www/html/

chown -R www-data:www-data /var/www/html

mkdir -p /var/www/html/data
chown -R www-data:www-data /var/www/html/data

if [ ! -f /var/run/crond.pid ]; then
    touch /var/run/crond.pid
fi
/usr/sbin/cron
chmod 0644 /etc/cron.d/mikhmon

exec "$@"
