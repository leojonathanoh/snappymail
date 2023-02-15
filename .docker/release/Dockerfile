FROM alpine:3.15 AS builder
RUN apk add --no-cache npm
RUN npm install -g gulp yarn
RUN apk add --no-cache php7 php7-json php-phar php-zip zip unzip
COPY . /source
WORKDIR /source
RUN yarn install
# Patch release.php with hotfix from: https://github.com/xgbstar1/snappymail-docker/blob/main/Dockerfile, so that release.php doesn't fail with error
RUN sed -i 's_^if.*rename.*snappymail.v.0.0.0.*$_if (!!system("mv snappymail/v/0.0.0 snappymail/v/{$package->version}")) {_' cli/release.php  || true
RUN php release.php
RUN ls build/dist/releases/webmail/ > /version
RUN find build/dist
RUN export VERSION=$(cat /version) && echo $VERSION && mv -v build/dist/releases/webmail/$VERSION/snappymail-$VERSION.zip /snappymail.zip && ls -al /snappymail.zip;
RUN unzip /snappymail.zip -d /snappymail; \
    # The 'www-data' user/group in alpine is 82:82. The 'nginx' user in alpine is 101:101, and is part of www-data group
    chown -R 82:82 /snappymail; \
    find /snappymail -type d -exec chmod 750 {} \; ; \
    find /snappymail -type f -exec chmod 640 {} \; ;


# Inspired by the original Rainloop dockerfile from youtous on GitLab
FROM php:7.4-fpm-alpine

LABEL org.label-schema.description="SnappyMail webmail client image using nginx, php-fpm on Alpine"

# Install dependencies such as nginx
RUN apk add --no-cache ca-certificates nginx supervisor bash

# Install PHP extensions
# gd
RUN apk add --no-cache freetype libpng libjpeg-turbo && \
    apk add --no-cache --virtual .deps freetype-dev libpng-dev libjpeg-turbo-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd && \
    apk del .deps
# intl
RUN apk add --no-cache icu-libs && \
    apk add --no-cache --virtual .deps icu-dev && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl && \
    apk del .deps
# ldap
RUN apk add --no-cache libldap && \
    apk add --no-cache --virtual .deps ldb-dev openldap-dev && \
    docker-php-ext-configure ldap && \
    docker-php-ext-install ldap && \
    apk del .deps
# mysql
RUN apk add --no-cache --virtual .deps libzip-dev && \
    docker-php-ext-install pdo_mysql && \
    apk del .deps
# opcache
RUN docker-php-ext-install opcache
# postgres
RUN apk add --no-cache libpq postgresql-dev && \
    apk add --no-cache --virtual .deps postgresql-dev && \
    docker-php-ext-install pdo_pgsql && \
    apk del .deps
# zip
RUN apk add --no-cache libzip && \
    apk add --no-cache --virtual .deps libzip-dev && \
    docker-php-ext-install zip && \
    apk del .deps

# Install snappymail
COPY --from=builder /snappymail /snappymail
# Install other content
COPY .docker/release/files /
RUN chmod +x /entrypoint.sh && chmod +x /logrotate-loop.sh && chown -R 82:82 /snappymail/data

# Create 'php-cli' user (unused)
RUN adduser -u 991 --disabled-password --shell /bin/bash --home /home/php-cli php-cli --gecos ""

WORKDIR /
VOLUME /snappymail/data
EXPOSE 8888
CMD ["/entrypoint.sh"]
