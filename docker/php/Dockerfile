FROM php:7.2.7-fpm-alpine

RUN apk update; \
    apk upgrade;

RUN set -ex \
    && apk --no-cache add \
    postgresql-dev

RUN docker-php-ext-install pgsql

RUN apk add --no-cache util-linux
