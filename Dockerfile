ARG PHP_VERSION

FROM docker-nexus.admlr.lipetsk.ru/php/base:${PHP_VERSION}

ARG BUILD_TAG
LABEL build_tag=$BUILD_TAG

# ADD ./php/010.php.ini /usr/local/etc/php/conf.d/010.php.ini
# ADD ./php/php.ini-production /usr/local/etc/php/php.ini
