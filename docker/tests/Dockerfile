# Test runner: PHP 8.3 with the WordPress core that the WordPress PHPUnit library loads.
FROM wordpress:6.8-php8.3-apache AS wordpress

FROM php:8.3-cli

COPY --from=wordpress /usr/src/wordpress /usr/src/wordpress
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
	&& apt-get install -y --no-install-recommends unzip \
	&& rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-install mysqli

WORKDIR /app
