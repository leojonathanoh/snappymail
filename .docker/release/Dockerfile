FROM alpine:3.15 AS builder
RUN apk add --no-cache npm
RUN npm install -g gulp yarn
RUN apk add --no-cache php7 php7-json php-phar php-zip
COPY . /source
WORKDIR /source
RUN yarn install
# Patch release.php with hotfix from: https://github.com/xgbstar1/snappymail-docker/blob/main/Dockerfile, so that release.php doesn't fail with error
RUN sed -i 's_^if.*rename.*snappymail.v.0.0.0.*$_if (!!system("mv snappymail/v/0.0.0 snappymail/v/{$package->version}")) {_' cli/release.php  || true
RUN php release.php
RUN set -eux; \
    VERSION=$( ls build/dist/releases/webmail ); \
    ls -al build/dist/releases/webmail/$VERSION/snappymail-$VERSION.tar.gz; \
    mkdir -p /snappymail; \
    tar -zxvf build/dist/releases/webmail/$VERSION/snappymail-$VERSION.tar.gz -C /snappymail; \
    # The 'www-data' user/group in alpine is 82:82. The 'nginx' user in alpine is 101:101, and is part of www-data group
    chown -R 82:82 /snappymail; \
    find /snappymail -type d -exec chmod 750 {} \; ; \
    find /snappymail -type f -exec chmod 640 {} \; ;


# Inspired by the original Rainloop dockerfile from youtous on GitLab
FROM php:8.1-fpm-alpine AS final

LABEL org.label-schema.description="SnappyMail webmail client image using nginx, php-fpm on Alpine"

# Install dependencies such as nginx
RUN apk add --no-cache ca-certificates nginx supervisor bash

# Install PHP extensions
# gd
RUN set -eux; \
    apk add --no-cache freetype libpng libjpeg-turbo; \
    apk add --no-cache --virtual .deps freetype-dev libpng-dev libjpeg-turbo-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install gd; \
    apk del .deps
# gnupg
RUN set -eux; \
    apk add --no-cache gnupg gpgme; \
    apk add --no-cache --virtual .deps gpgme-dev; \
    apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS; \
    pecl install gnupg; \
    docker-php-ext-enable gnupg; \
    docker-php-source delete; \
    apk del .build-dependencies; \
    apk del .deps
# intl
RUN set -eux; \
    apk add --no-cache icu-libs; \
    apk add --no-cache --virtual .deps icu-dev; \
    docker-php-ext-configure intl; \
    docker-php-ext-install intl; \
    apk del .deps
# ldap
RUN set -eux; \
    apk add --no-cache libldap; \
    apk add --no-cache --virtual .deps ldb-dev openldap-dev; \
    docker-php-ext-configure ldap; \
    docker-php-ext-install ldap; \
    apk del .deps
# mysql
RUN set -eux; \
    apk add --no-cache --virtual .deps libzip-dev; \
    docker-php-ext-install pdo_mysql; \
    apk del .deps
# opcache
RUN docker-php-ext-install opcache
# postgres
RUN set -eux; \
    apk add --no-cache libpq postgresql-dev; \
    apk add --no-cache --virtual .deps postgresql-dev; \
    docker-php-ext-install pdo_pgsql; \
    apk del .deps
# redis
RUN set -eux; \
    apk add --no-cache liblzf zstd; \
    apk add --no-cache --virtual .deps liblzf-dev zstd-libs; \
    apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS; \
    pecl install igbinary; \
    docker-php-ext-enable igbinary; \
    pecl install --configureoptions 'enable-redis-igbinary="yes" enable-redis-lzf="yes" enable-redis-zstd="yes"' redis; \
    docker-php-ext-enable redis; \
    docker-php-source delete; \
    apk del .build-dependencies; \
    apk del .deps
# zip
RUN set -eux; \
    apk add --no-cache libzip; \
    apk add --no-cache --virtual .deps libzip-dev; \
    docker-php-ext-install zip; \
    apk del .deps

# Install snappymail
COPY --from=builder /snappymail /snappymail
# Install other content
COPY .docker/release/files /
RUN chmod +x /entrypoint.sh && chmod +x /logrotate-loop.sh && chown -R 82:82 /snappymail/data

WORKDIR /snappymail
VOLUME /snappymail/data
EXPOSE 8888
CMD ["/entrypoint.sh"]
