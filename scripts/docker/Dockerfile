
# Tsugi plus mysql
# docker build --tag tsugi_pg4e .
# docker run -p 8080:80 -p 3306:3306 -e BOB=42 -dit tsugi_pg4e:latest

# mysql -u root --host=127.0.0.1 --port=3306 -p

# See: https://gist.github.com/sheikhwaqas/9088872 

FROM tsugi_dev:latest

COPY tsugi-pg4e-startup.sh /usr/local/bin

# Install.
RUN \
  export DEBIAN_FRONTEND=noninteractive && \
  export LC_ALL=C.UTF-8 && \
  echo ======= Update 1 && \
  apt-get update && \
  echo ======= Install MySQL && \
  apt install postgresql postgresql-contrib && \
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
