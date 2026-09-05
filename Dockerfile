FROM php:8.4-cli

ADD ./ /srv/

WORKDIR /srv

RUN apt-get -y update \
	&& apt-get install -y libicu-dev libxml2-dev \
	&& docker-php-ext-configure intl \
	&& docker-php-ext-install intl dom

RUN usermod -a -G lp root

USER root

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host", "0.0.0.0", "--port", "8000"]
