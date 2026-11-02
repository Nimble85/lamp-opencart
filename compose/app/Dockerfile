FROM php:8.3-apache

ARG OPENCART_URL=https://github.com/opencart/opencart/releases/download/4.1.0.4/opencart-4.1.0.4.zip

RUN apt-get update && apt-get install -y --no-install-recommends \
        unzip \
        curl \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd zip mysqli \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL -o /tmp/opencart.zip "${OPENCART_URL}" \
    && unzip -q /tmp/opencart.zip -d /tmp/opencart \
    && mv /tmp/opencart/upload/* /var/www/html/ \
    && rm -rf /tmp/opencart.zip /tmp/opencart \
    && chown -R www-data:www-data /var/www/html

# Ukrainian (uk-ua) language pack — baked into the image so every scaled replica has it,
# not just whichever one an admin happens to upload it to. install-ukrainian-language.php
# does the DB-side registration (see install-once.sh) once the schema exists.
COPY lang/ukrainian.ocmod.zip /tmp/ukrainian.ocmod.zip
RUN mkdir -p /var/www/html/extension/ukrainian \
    && unzip -q /tmp/ukrainian.ocmod.zip -d /var/www/html/extension/ukrainian \
    && cp -r /var/www/html/extension/ukrainian/extension/opencart/. /var/www/html/extension/opencart/ \
    && rm -f /tmp/ukrainian.ocmod.zip \
    && chown -R www-data:www-data /var/www/html/extension

COPY entrypoint.sh /entrypoint.sh
COPY install-once.sh /install-once.sh
COPY install-ukrainian-language.php /install-ukrainian-language.php
RUN chmod +x /entrypoint.sh /install-once.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
