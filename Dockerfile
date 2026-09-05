FROM php:8.4-cli

ADD ./ /srv/

WORKDIR /srv

RUN apt-get -y update \
        && apt-get install -y libicu-dev libxml2-dev libpng-dev libzip-dev \
        && docker-php-ext-configure intl \
        && docker-php-ext-install intl dom gd zip pdo pdo_mysql \
        && apt-get clean \
        && rm -rf /var/lib/apt/lists/*

# Install Node.js for asset building
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN usermod -a -G lp root

RUN mkdir -p /srv/database && chown -R root:root /srv/database

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host", "0.0.0.0", "--port", "8000"]
