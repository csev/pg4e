
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
import sys
import hidden
import time
import myutils
from email.message import EmailMessage

quota = 60000000

dryrun = True
if len(sys.argv) == 2 and sys.argv[1] == "delete" :
    print('This is the real deal!')
    dryrun = False

# Load the secrets for the readwrite shared DB
secrets = hidden.master()

conn = psycopg2.connect(
        host=secrets['host'],
        port=secrets['port'],
        # database=secrets['database'],
        user=secrets['user'],
        password=secrets['pass'],
        connect_timeout=3)
cur = conn.cursor()

sql = "select datname,oid from pg_database where datname='pg4e_025ca';"
sql = "SELECT datname FROM pg_database;"
# row = cur.execute(sql, fields)

sql = "SELECT setting FROM pg_settings WHERE name = 'data_directory';"
data_directory = myutils.queryValue(cur, sql)

sql = "SELECT datname,oid FROM pg_database ORDER BY oid;"
stmt = cur.execute(sql)

toolarge = list()
while True :
    row = cur.fetchone() 
    if not row : break
    db_name = row[0]
    if not db_name.startswith('pg4e_') : continue
    db_oid = row[1]
    now = time.time()
    db_folder = data_directory + '/base/' + str(db_oid)
    modified = os.path.getmtime(db_folder)
    folder_days = int((now - modified)/(60*60*24))

    # Check the latest file modification date
    tot = 0
    count = 0
    for dirpath, dnames, fnames in os.walk(db_folder):

        for fname in fnames:
            fpath = db_folder + '/' + fname
            size = os.path.getsize(fpath)
            tot += size
            count += 1
            # print('   ', fpath, size)

    # If files have been changing
    if tot < quota : continue
    print (db_name, db_folder, count, tot)
    toolarge.append((db_name, db_folder, count, tot))

if not dryrun :
    print("Here we go - going to delete", len(toolarge))
    time.sleep(5)

print()
actions = list()
for db in toolarge:
    sql = 'DROP DATABASE '+db[0]+';'
    actions.append(sql+' -- count='+str(db[2])+' tot='+str(db[3]))
    if not dryrun:
        print(sql)
        time.sleep(1)
        stmt = cur.execute(sql)

cur.close()

print(actions)

# Send some email
if len(actions) > 0 :
    message = "Subject: Postgres Quota Actions ("+str(len(actions))+")\n\n"
    if dryrun: message = message + "This is a dry run\n\n";
    for action in actions:
        message = message + action + "\n";
    print(message)
    myutils.sendMail(message)

