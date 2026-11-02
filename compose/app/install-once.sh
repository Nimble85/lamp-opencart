#!/bin/sh
set -e

echo "[install] waiting for MySQL at ${MYSQL_HOST}..."
until php -r '
$m = @new mysqli(getenv("MYSQL_HOST"), "root", getenv("MYSQL_ROOT_PASSWORD"));
exit($m->connect_errno ? 1 : 0);
'; do
    sleep 2
done
echo "[install] MySQL is reachable."

cp /var/www/html/config-dist.php /var/www/html/config.php
cp /var/www/html/admin/config-dist.php /var/www/html/admin/config.php

php /var/www/html/install/cli_install.php install \
    --username "${OC_ADMIN_USER}" \
    --password "${OC_ADMIN_PASS}" \
    --email "${OC_ADMIN_EMAIL}" \
    --http_server "${OC_HTTP_SERVER}" \
    --db_driver mysqli \
    --db_hostname "${MYSQL_HOST}" \
    --db_username root \
    --db_password "${MYSQL_ROOT_PASSWORD}" \
    --db_database "${MYSQL_DATABASE}" \
    --db_port 3306 \
    --db_prefix oc_

echo "[install] registering Ukrainian (uk-ua) language..."
php /install-ukrainian-language.php "${MYSQL_HOST}" root "${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" oc_

mkdir -p /shared-config
cp /var/www/html/config.php /shared-config/config.php
cp /var/www/html/admin/config.php /shared-config/admin-config.php

echo "[install] OpenCart install finished, config exported to /shared-config."
