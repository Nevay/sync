FROM php:8.1-alpine

RUN set -eux; \
    apk add --no-cache \
      git \
    ;

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions; \
    install-php-extensions \
      ev \
      opcache \
      pcov \
      @composer \
    ;
