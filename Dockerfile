FROM quay.io/keboola/docker-base-php56:0.0.2

ADD . /code
WORKDIR /code

RUN composer install --no-interaction

ENTRYPOINT php ./main.php --data=/data
