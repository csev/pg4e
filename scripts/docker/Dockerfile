
# Tsugi plus mysql
# docker build --tag tsugi_pg4e .
# docker run -p 8080:80 -p 3306:3306 -e TSUGI_SERVICENAME=PG4E -e MYSQL_ROOT_PASSWORD=secret -e MAIN_REPO=https://github.com/csev/pg4e --volume-driver=nfs  -v /Users/csev/docker:/mnt/csev --name pg4e -dit tsugi_pg4e:latest
# docker exec -it pg4e bash

# mysql -u root --host=127.0.0.1 --port=3306 -p

FROM tsugi_dev:latest

COPY tsugi-pg4e-startup.sh /usr/local/bin

# Install.
# apt-get update
# apt-cache search postgresql
RUN \
  export DEBIAN_FRONTEND=noninteractive && \
  export LC_ALL=C.UTF-8 && \
  echo ======= Update 1 && \
  wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add - && \
  echo "deb http://apt.postgresql.org/pub/repos/apt/ bionic-pgdg main" | tee /etc/apt/sources.list.d/pgdg.list && \
  apt-get update && \
  echo ======= Install PostgreSQL 11 && \
  apt install -y postgresql-11 postgresql-contrib-11 && \
  apt install -y postgresql-client-11 && \
  apt install -y php7.3-pgsql && \
  echo ======= Cleanup Starting && \
  df && \
  rm -rf /var/lib/apt/lists/* && \
  df && \
  echo ======= Cleanup Done

#  apt-get install -y mailutils

# Set environment variables.
ENV HOME /root

# Define working directory.
WORKDIR /root

EXPOSE 80 443 3306
ENTRYPOINT ["bash", "/usr/local/bin/tsugi-pg4e-startup.sh"]

# Define default command.
CMD ["bash"]
