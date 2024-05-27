
# Cleanup idle connections

# python3 idlers.py

import psycopg2
import hidden
import myutils

# How many idle processes we leave alone
limit = 120

# Load the secrets for the readwrite shared DB
secrets = hidden.master()

conn = psycopg2.connect(
        host=secrets['host'],
        port=secrets['port'],
        # database=secrets['database'],
        user=secrets['user'],
        password=secrets['pass'],
        connect_timeout=3)

# https://stackoverflow.com/questions/34484066/create-a-postgres-database-using-python
conn.autocommit = True
cur = conn.cursor()

sql = "select count(pg_terminate_backend(pid)) from (SELECT pid FROM pg_stat_activity WHERE state = 'idle' and usename like 'pg4e_%' order by state_change desc offset 120) AS idlers;"
print(sql);
count = myutils.queryValue(cur, sql)
print('Idlers removed:', count)

