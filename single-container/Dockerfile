FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
        apache2 \
        php php-cli php-mysql php-gd php-zip php-mbstring php-xml php-curl libapache2-mod-php \
        mysql-server \
        unzip curl ca-certificates \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

ARG OPENCART_URL=https://github.com/opencart/opencart/releases/download/4.1.0.4/opencart-4.1.0.4.zip
RUN curl -fsSL -o /tmp/opencart.zip "${OPENCART_URL}" \
    && unzip -q /tmp/opencart.zip -d /tmp/opencart \
    && rm -rf /var/www/html \
    && mv /tmp/opencart/upload /var/www/html \
    && rm -rf /tmp/opencart.zip /tmp/opencart \
    && chown -R www-data:www-data /var/www/html

# Ukrainian (uk-ua) language pack — baked into the image at build time (same package as in
# compose/app/). install-ukrainian-language.php does the DB-side registration once cli_install.php
# has created the schema (called from entrypoint.sh below).
COPY lang/ukrainian.ocmod.zip /tmp/ukrainian.ocmod.zip
RUN mkdir -p /var/www/html/extension/ukrainian \
    && unzip -q /tmp/ukrainian.ocmod.zip -d /var/www/html/extension/ukrainian \
    && cp -r /var/www/html/extension/ukrainian/extension/opencart/. /var/www/html/extension/opencart/ \
    && rm -f /tmp/ukrainian.ocmod.zip \
    && chown -R www-data:www-data /var/www/html/extension

COPY entrypoint.sh /entrypoint.sh
COPY install-ukrainian-language.php /install-ukrainian-language.php
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
