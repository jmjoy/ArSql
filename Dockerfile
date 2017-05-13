FROM webdevops/php-nginx:ubuntu-12.04

WORKDIR /

RUN apt-get update && apt-get upgrade

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    chmod a+x composer.phar && \
    mv composer.phar /usr/bin/composer

WORKDIR /arSql
