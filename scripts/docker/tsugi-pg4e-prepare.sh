export DEBIAN_FRONTEND=noninteractive
export LC_ALL=C.UTF-8
echo ======= Update 1

# Postgres Keys
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
echo "deb http://apt.postgresql.org/pub/repos/apt/ bionic-pgdg main" | tee /etc/apt/sources.list.d/pgdg.list

# Elastic keys
wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | apt-key add -
echo "deb https://artifacts.elastic.co/packages/7.x/apt stable main" | tee /etc/apt/sources.list.d/elastic-7.x.list

apt-get update

echo ======= Install PostgreSQL 11
apt install -y sudo
apt install -y postgresql-11 postgresql-contrib-11
apt install -y postgresql-client-11
apt install -y php7.3-pgsql

echo ======= Install Elastic 7

apt-get -y install openjdk-8-jdk

apt-get install -y python3-venv
apt-get install -y nodejs
apt-get install -y npm
apt-get install -y apt-transport-https
apt-get install -y elasticsearch

echo ======= Check out server code

cd /
git clone https://github.com/csev/charles-server.git
cd charles-server

echo ====== Making virtual environment
python3 -m venv .venv
source .venv/bin/activate

# Get the latest pip
pip install --upgrade pip
pip install -r .requirements
deactivate

echo ======= Cleanup Starting
df
rm -rf /var/lib/apt/lists/*
df
echo ======= Cleanup Done

#  apt-get install -y mailutils
