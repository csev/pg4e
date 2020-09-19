
# Cleanup unused databases

# python3 cleanup_psql.py

# SELECT v.id as id, regionid, ym, ym_val, city, state, metro, county FROM home_value AS v
# JOIN home_city ON city_id = home_city.id 
# JOIN home_state ON state_id = home_state.id
# JOIN home_metro ON metro_id = home_metro.id
# JOIN home_county ON county_id = home_county.id
# LIMIT 5;

import psycopg2
import os
import hidden
import time
import myutils
import datetime

# Load the secrets for the readwrite shared DB
secrets = hidden.master()

conn = psycopg2.connect(
        host=secrets['host'],
        port=secrets['port'],
        database='charles',
        user=secrets['user'],
        password=secrets['pass'],
        connect_timeout=3)
cur = conn.cursor()

sql = "SELECT data FROM access_statuses LIMIT 10;";
sql = "SELECT data->>'index' AS index, data ->> 'accessed' AS access FROM access_statuses ORDER BY data ->> 'accessed';";
stmt = cur.execute(sql)

expired = list()
while True :
    row = cur.fetchone() 
    if not row : break
    if not row[0].startswith('pg4e_') : continue
    iso_time = time.mktime(time.strptime(row[1], "%Y-%m-%dT%H:%M:%S.%f"))
    days = int((time.time() - iso_time)/(60*60*24))
    if days < 60 : continue

    print(row[0], row[1], days)
    expired.append(row[0])

print('Dry run - this would delete ', len(expired))

for es in expired :
    print('delete', es)

print()
print("python3 elastictool.py")
