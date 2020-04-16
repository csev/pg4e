
echo "Running PG4E Startup"

bash /usr/local/bin/tsugi-dev-startup.sh return

echo Starting PostgreSQL
service postgresql start

echo "Starting elasticsearch"

service --status-all
service elasticsearch start

CHARLES_POSTGRES_HOST=localhost ; export CHARLES_POSTGRES_HOST
CHARLES_POSTGRES_PORT=5432 ; export CHARLES_POSTGRES_PORT
CHARLES_ELASTICSEARCH_URI=http://localhost:9200 ; export CHARLES_ELASTICSEARCH_URI

# Test with
# curl -X GET http://127.0.0.1:8001/v1/elasticsearch
# {"errors":[{"title":"Scope Error","detail":"No token provided.","status":403}]}

CHARLES_POSTGRES_USER=charles; export CHARLES_POSTGRES_USER
if [ -z "$CHARLES_POSTGRES_PASSWORD" ]; then
CHARLES_POSTGRES_PASSWORD=password; export CHARLES_POSTGRES_PASSWORD;
fi
if [ -z "$CHARLES_AUTH_SECRET" ]; then
CHARLES_AUTH_SECRET=12345; export CHARLES_AUTH_SECRET;
fi

COMPLETE=/usr/local/bin/tsugi-pg4e-complete
if [ -f "$COMPLETE" ]; then
    echo "Starting charles-server"
    cd /charles-server
    source .venv/bin/activate
    python /charles-server/server --port 8001

    echo "https://certbot.eff.org/lets-encrypt/ubuntubionic-apache"
    echo " "
    echo "certbot --apache --dry-run"
    echo "cron: certbot renew --dry-run"
    echo "PG4E Startup Already has run"
else

# https://stackoverflow.com/questions/18715345/how-to-create-a-user-for-postgres-from-the-command-line-for-bash-automation
if [ -z "$PSQL_ROOT_PASSWORD" ]; then
PSQL_ROOT_PASSWORD=password; export PSQL_ROOT_PASSWORD;
fi

echo "Setting psql root password to $PSQL_ROOT_PASSWORD"
sudo -i -u postgres psql -c "ALTER ROLE postgres WITH PASSWORD '$PSQL_ROOT_PASSWORD'"

echo "Creating user/database for charles-server with password $CHARLES_POSTGRES_PASSWORD"
sudo -i -u postgres psql -c "CREATE USER charles WITH PASSWORD '$CHARLES_POSTGRES_PASSWORD'"
sudo -i -u postgres psql -c "CREATE DATABASE charles WITH OWNER charles"

echo "Removing phpMyAdmin"
rm -rf /var/www/html/phpMyAdmin /var/www/html/phppgadmin

echo "Adding and configuring phppgadmin"
cd /var/www/html/
git clone https://github.com/csev/phppgadmin.git
cp /var/www/html/scripts/config.inc.php /var/www/html/phppgadmin/conf/config.inc.php

cat >> /var/www/html/tsugi/config.php << EOF 
\$CFG->tool_folders = array("admin", "../tools", "mod");
\$CFG->psql_root_password = "$PSQL_ROOT_PASSWORD";

EOF

# Open things up all but postgres user
# https://dba.stackexchange.com/questions/83984/connect-to-postgresql-server-fatal-no-pg-hba-conf-entry-for-host
# https://stackoverflow.com/questions/61179852/how-to-configure-postgessql-to-accept-all-incoming-connections-except-postgres
cat >> /etc/postgresql/11/main/pg_hba.conf << EOF
host    all             postgres        0.0.0.0/0               reject
host    all             charles         127.0.0.1/32            md5
host    all             charles         0.0.0.0/0               reject
host    all             all             0.0.0.0/0               md5
EOF

# https://blog.bigbinary.com/2016/01/23/configure-postgresql-to-allow-remote-connection.html
rm /tmp/x
sed "s/#listen_addresses = 'localhost'/listen_addresses = '*'/" < /etc/postgresql/11/main/postgresql.conf > /tmp/x
cp /tmp/x /etc/postgresql/11/main/postgresql.conf

cat > /root/.vimrc << EOF
set sw=4 ts=4 sts=4 et
filetype plugin indent on
autocmd FileType java setlocal sw=4 ts=4 sts=4 noet
syntax on
EOF

echo "Restart PostgreSQL"
service postgresql restart

# Fix the composer bits
EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

# php composer-setup.php --quiet
php composer-setup.php --install-dir=/usr/local/bin
RESULT=$?
rm composer-setup.php

# Run composer
PWD=`pwd`
cd /var/www/html/tools/sql
php /usr/local/bin/composer.phar install
echo $PWD

fi

echo "Starting charles-server"
cd /charles-server
source .venv/bin/activate
python /charles-server/server --port 8001

echo "https://certbot.eff.org/lets-encrypt/ubuntubionic-apache"
echo " "
echo "certbot --apache --dry-run"
echo "cron: certbot renew --dry-run"

touch $COMPLETE

echo ""
if [ "$@" == "return" ] ; then
  echo "Tsugi PG4E Startup Returning..."
  exit
fi

exec bash /usr/local/bin/monitor-apache.sh

# Should never happen
# https://stackoverflow.com/questions/2935183/bash-infinite-sleep-infinite-blocking
echo "Tsugi PG4E Sleeping forever..."
while :; do sleep 2073600; done

