
# Tsugi PG4E Test harness

# Pre-Requisite - build these - https://github.com/tsugiproject/docker-php.git

# docker build --tag tsugi_pg4e .

# docker run -p 8080:80 -p 3306:3306 -p 5000:5432 -p 8001:8001 -e TSUGI_APPHOME=http://localhost:8080 -e TSUGI_SERVICENAME=PG4E -e MYSQL_ROOT_PASSWORD=secret -e PSQL_ROOT_PASSWORD=secret -e MAIN_REPO=https://github.com/csev/pg4e --volume-driver=nfs  -v /Users/csev/docker:/mnt/csev --name pg4e -dit tsugi_pg4e:latest

# navigate to http://localhost:8080/

# docker exec -it pg4e bash

# Should fail
# host: psql -h 127.0.0.1 -p 5000 -U postgres -W

# dock: psql -h 127.0.0.1 -p 5432 -U postgres -W
# dock: CREATE USER x WITH PASSWORD 'y';
# dock: CREATE DATABASE pg4e WITH OWNER x;

# host: psql -h 127.0.0.1 -p 5000 -U x pg4e

# curl -X GET http://127.0.0.1:8001/v1/elasticsearch
# {"errors":[{"title":"Scope Error","detail":"No token provided.","status":403}]}

# es_prefix: v1/basicauth/elasticsearch

# mysql -u root --host=127.0.0.1 --port=3306 -p

FROM tsugi_dev:latest

COPY tsugi-pg4e-prepare.sh /usr/local/bin
COPY tsugi-pg4e-startup.sh /usr/local/bin

# Install.
RUN bash /usr/local/bin/tsugi-pg4e-prepare.sh

#  apt-get install -y mailutils

# Set environment variables.
ENV HOME /root

# Define working directory.
WORKDIR /root

EXPOSE 80 443 3306
ENTRYPOINT ["bash", "/usr/local/bin/tsugi-pg4e-startup.sh"]

# Define default command.
CMD ["bash"]
