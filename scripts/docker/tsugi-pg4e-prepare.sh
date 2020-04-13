export DEBIAN_FRONTEND=noninteractive
export LC_ALL=C.UTF-8
echo ======= Update 1
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
echo "deb http://apt.postgresql.org/pub/repos/apt/ bionic-pgdg main" | tee /etc/apt/sources.list.d/pgdg.list
apt-get update
echo ======= Install PostgreSQL 11
apt install -y sudo
apt install -y postgresql-11 postgresql-contrib-11
apt install -y postgresql-client-11
apt install -y php7.3-pgsql
echo ======= Cleanup Starting
df
rm -rf /var/lib/apt/lists/*
df
echo ======= Cleanup Done

#  apt-get install -y mailutils
