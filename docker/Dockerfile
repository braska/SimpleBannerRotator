FROM alpine:3.3
COPY ./docker/nginx /etc/nginx
COPY . /srv/www/app
WORKDIR /srv/www/app
RUN cp ./docker/config.ini /srv/www/app/app/config/config.ini
CMD /bin/true
