#!/bin/bash
set -e

service mysql start
chmod 755 /var/run/mysqld

mysql -u root <<-SQL
CREATE DATABASE IF NOT EXISTS opencart;
CREATE USER IF NOT EXISTS 'opencart_user'@'localhost' IDENTIFIED BY 'OpencartUserPw123!';
GRANT ALL PRIVILEGES ON opencart.* TO 'opencart_user'@'localhost';
FLUSH PRIVILEGES;
SQL

if [ ! -f /var/www/html/config.php.installed ]; then
    cp /var/www/html/config-dist.php /var/www/html/config.php
    cp /var/www/html/admin/config-dist.php /var/www/html/admin/config.php

    php /var/www/html/install/cli_install.php install \
        --username admin \
        --password "AdminPass123!" \
        --email admin@example.com \
        --http_server "http://localhost:8082/" \
        --db_driver mysqli \
        --db_hostname localhost \
        --db_username opencart_user \
        --db_password "OpencartUserPw123!" \
        --db_database opencart \
        --db_port 3306 \
        --db_prefix oc_

    echo "[entrypoint] registering Ukrainian (uk-ua) language..."
    php /install-ukrainian-language.php localhost opencart_user "OpencartUserPw123!" opencart oc_

    rm -rf /var/www/html/install
    touch /var/www/html/config.php.installed
    chown -R www-data:www-data /var/www/html
fi

exec apache2ctl -D FOREGROUND
