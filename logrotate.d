/var/www/html/data/sync.log {
    daily
    rotate 30
    missingok
    notifempty
    copytruncate
    su www-data www-data
    compress
    delaycompress
}
