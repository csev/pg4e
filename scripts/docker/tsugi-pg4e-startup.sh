echo "Running MySQL Startup"

bash /usr/local/bin/tsugi-dev-startup.sh return

echo Starting PostgreSQL
service postgresql start

# https://stackoverflow.com/questions/18715345/how-to-create-a-user-for-postgres-from-the-command-line-for-bash-automation
if [ -z "$PSQL_ROOT_PASSWORD" ]; then
    echo "Setting psql root password to default pw"
    sudo -i -u postgres psql -c "ALTER ROLE postgres WITH PASSWORD 'password'"
else
    echo "Setting psql root password to $PSQL_ROOT_PASSWORD"
    sudo -i -u postgres psql -c "ALTER ROLE postgres WITH PASSWORD '$PSQL_ROOT_PASSWORD'"
fi

# psql -h 127.0.0.1 -U postgres -W

cat >> /var//www/html/tsugi/config.php << EOF 
\$CFG->tool_folders = array("admin", "../tools", "mod");

EOF

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

