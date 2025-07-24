FROM hyperf/hyperf:8.3-alpine-v3.21-swoole-v6.0.2

LABEL maintainer="PHP Training Developers <hello@php.training>" version="1.0" license="MIT" app.name="PHP_Training"

ARG USERID
ARG GROUPID
ARG timezone

ENV TIMEZONE=${timezone:-"America/Sao_Paulo"} \
    APP_ENV=prod \
    SCAN_CACHEABLE=(true)

# update
RUN set -ex \
    # show php version and extensions
    && php -v \
    && php -m \
    && php --ri swoole \
    #  ---------- some config ----------
    && cd /etc/php83 \
    # - config PHP
    && { \
        echo "upload_max_filesize=128M"; \
        echo "post_max_size=128M"; \
        echo "memory_limit=1G"; \
        echo "date.timezone=${TIMEZONE}"; \
    } | tee conf.d/99_overrides.ini \
    # - config timezone
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    # ---------- clear works ----------
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"

RUN addgroup -g $GROUPID -S hyperf
RUN adduser -u $USERID -G hyperf -S hyperf

# install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir -p /var/www && chown -R hyperf:hyperf /var/www

RUN apk add --no-cache --virtual .build-deps \
        build-base \
        autoconf \
        automake \
        libtool \
        wget \
    && cd /tmp \
    && wget https://github.com/emcrisostomo/fswatch/releases/download/1.14.0/fswatch-1.14.0.tar.gz \
    && tar -xf fswatch-1.14.0.tar.gz \
    && cd fswatch-1.14.0 \
    && ./configure \
    && make \
    && make install \
    && apk del .build-deps \
    && rm -rf /tmp/*

USER hyperf

WORKDIR /var/www

