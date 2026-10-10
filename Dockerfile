ARG PHP_VERSION

# Базовый образ с PHP. Укажите свой реестр через --build-arg REGISTRY=...
ARG REGISTRY=docker.io/library
FROM ${REGISTRY}/php:${PHP_VERSION}-fpm

ARG BUILD_TAG
LABEL build_tag=$BUILD_TAG

# ADD ./php/010.php.ini /usr/local/etc/php/conf.d/010.php.ini
# ADD ./php/php.ini-production /usr/local/etc/php/php.ini
