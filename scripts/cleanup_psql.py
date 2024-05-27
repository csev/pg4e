
# Cleanup unused databases

# python3 cleanup_psql.py

import psycopg2
import os
import hidden
import time
import myutils
import sys
import math
from email.message import EmailMessage

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

# https://stackoverflow.com/questions/34484066/create-a-postgres-database-using-python
conn.autocommit = True
cur = conn.cursor()

sql = "select datname,oid from pg_database where datname='pg4e_025ca';"
sql = "SELECT datname FROM pg_database;"
# row = cur.execute(sql, fields)

sql = "SELECT setting FROM pg_settings WHERE name = 'data_directory';"
data_directory = myutils.queryValue(cur, sql)
print('Data directory', data_directory)

sql = "SELECT datname,oid FROM pg_database ORDER BY oid;"
stmt = cur.execute(sql)

expired = list()
while True :
    row = cur.fetchone() 
    if not row : break
    db_name = row[0]
    if db_name.startswith('pg4e_data') : continue
    if not db_name.startswith('pg4e_') : continue
    db_oid = row[1]

    # Time chunked to days
    dayseconds = 24*60*60
    now = int(time.time()/dayseconds)*dayseconds
    db_folder = data_directory + '/base/' + str(db_oid)
    modified = os.path.getmtime(db_folder)
    folder_days = int(math.trunc(now - modified)/(60*60*24))

    # Check the latest file modification date
    file_mod = myutils.mtime(db_folder)
    print( "last modified: %s" % time.ctime(os.path.getmtime(db_folder)))
    print((db_name, db_folder, folder_days, file_mod))

    # We want to the folder to be at least 60 days old (i.e. creation
    # date) and no files have been changing for 30 days
    if folder_days < 60 : continue
    if file_mod < 30: continue
    print (db_name, db_folder, folder_days, file_mod)
    print( "last modified: %s" % time.ctime(os.path.getmtime(db_folder)))
    expired.append((db_name, db_folder, folder_days, file_mod))

if not dryrun :
    print("Here we go - going to delete", len(expired))
    time.sleep(5)

print()
actions = list()
for db in expired:
    sql = 'DROP DATABASE '+db[0]+';'
    actions.append(sql+' -- folder_days='+str(db[2])+' file_mod='+str(db[3]))
    if not dryrun:
        print(sql)
        time.sleep(1)
        stmt = cur.execute(sql)

cur.close()

# Send some email
if len(actions) > 0 :
    subject = "Subject: Postgres Expire Actions ("+str(len(actions))+")"
    message = ''
    if dryrun: message = message + "This is a dry run\n\n";
    for action in actions:
        message = message + action + "\n";
    print(message)
    print(myutils.sendNotification(subject,message))

