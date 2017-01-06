FROM alpine:3.3
COPY ./docker/nginx /etc/nginx
COPY . /srv/www/app
WORKDIR /srv/www/app
RUN cp ./docker/config.ini /srv/www/app/app/config/config.ini && \
	apk update && \
	apk add --no-cache git php php-phar php-json php-ctype php-openssl && \
	php composer.phar install && \
	apk del git php php-phar php-json php-ctype php-openssl
CMD /bin/true
