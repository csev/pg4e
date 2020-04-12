echo "Running MySQL Startup"

bash /usr/local/bin/tsugi-dev-startup.sh return

echo Starting PostgreSQL
service postgresql start

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

