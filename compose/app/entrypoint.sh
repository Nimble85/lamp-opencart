#!/bin/sh
set -e

echo "[app] waiting for install job to publish config..."
until [ -f /shared-config/config.php ] && [ -f /shared-config/admin-config.php ]; do
    sleep 2
done

cp /shared-config/config.php /var/www/html/config.php
cp /shared-config/admin-config.php /var/www/html/admin/config.php
chown www-data:www-data /var/www/html/config.php /var/www/html/admin/config.php
chmod -R 777 /var/www/html/system/storage /var/www/html/image
rm -rf /var/www/html/install

exec "$@"
