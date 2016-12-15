FROM quay.io/keboola/docker-base-php56:0.0.2
COPY . /home/
ENTRYPOINT php /home/main.php

