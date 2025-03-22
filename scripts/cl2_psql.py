
# Cleanup unused databases

# python3 cleanup_psql.py

import psycopg2
import os
import hidden
import time
import datetime
import myutils
import sys
import math
from email.message import EmailMessage

limit = 10

# First we keep it if the folder's modified date is < 50 days old
expire_days_modified_folder = 60

# If folder modified date is > 60 days old, we check the database dates
expire_days_created = 90
expire_days_updated = 50

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

sql = "SELECT count(*) FROM pg_database;"
db_count = myutils.queryValue(cur, sql)
print("Database count ", db_count)

sql = "SELECT setting FROM pg_settings WHERE name = 'data_directory';"
data_directory = myutils.queryValue(cur, sql)
print('Data directory', data_directory)

# https://stackoverflow.com/questions/24806122/get-database-creation-date-on-postgresql
sql = "SELECT datname,oid,(pg_stat_file('base/'||oid ||'/PG_VERSION', true)).modification FROM pg_database ORDER BY oid;"
print(sql)
stmt = cur.execute(sql)

expired = list()
conn2 = False
cur2 = False
keep = 0
scanned = 0
while True :
    scanned = scanned + 1
    if len(expired) >= limit : break
    row = cur.fetchone()
    if not row : break
    db_name = row[0]
    if db_name.startswith('pg4e_data') : continue
    if not db_name.startswith('pg4e_') : continue
    db_oid = row[1]
    db_stat = row[2] # Modification date of the database
    now_at=datetime.datetime.now().astimezone()

    # https://stackoverflow.com/questions/5476065/how-to-truncate-the-time-on-a-datetime-object
    now_at = now_at.replace(hour=0, minute=0, second=0, microsecond=0, tzinfo=None)
    try:
        db_stat = db_stat.replace(hour=0, minute=0, second=0, microsecond=0, tzinfo=None)
        ts_diff=now_at-db_stat

        f_days=int(ts_diff.total_seconds() / (60*60*24))
    except:
        # failure to parse a date  pg4e_217c722bf9 00:00:00 None
        print('failure to parse db_stat', db_name, db_stat)
        f_days = 999

    if f_days < expire_days_modified_folder:
        keep = keep + 1
        # print(db_name, 'keep f_days', f_days)
        continue

    # The fails passes the inexpensive "recent enough" test, lets check the DB itself
    if (scanned % 10 == 0) : time.sleep(1)

    if cur2 is not False:
        try:
            cur2.close()
        except:
            pass
        cur2 = False
    if conn2 is not False:
        try:
            conn2.close()
        except:
            pass
        conn2 = False

    try:
        conn2 = psycopg2.connect(
            host=secrets['host'],
            port=secrets['port'],
            database=db_name,
            user=secrets['user'],
            password=secrets['pass'],
            connect_timeout=3)

        cur2 = conn2.cursor()
        sql = "SELECT valstr, created_at, updated_at  FROM pg4e_meta WHERE keystr='access';"
        stmt = cur2.execute(sql)
        row = cur2.fetchone()
    except:
        print(db_name, "Schema fail", f_days)
        expired.append((db_name, "Schema fail",0,0))
        continue

    if row == None or len(row) != 3 :
        print(db_name, "p4e_meta missing", f_days)
        expired.append((db_name, "p4e_meta missing",0,0))
        continue

    created_at = row[1]
    if created_at is not None : created_at = created_at.replace(hour=0, minute=0, second=0, microsecond=0, tzinfo=None)
    updated_at = row[2]
    if updated_at is not None : updated_at = updated_at.replace(hour=0, minute=0, second=0, microsecond=0, tzinfo=None)
    now_at=datetime.datetime.now()
    now_at = now_at.replace(hour=0, minute=0, second=0, microsecond=0, tzinfo=None)

    # https://stackoverflow.com/questions/1345827/how-do-i-find-the-time-difference-between-two-datetime-objects-in-python
    if created_at is None:
        c_days = 999
    else:
        ts_diff=now_at-created_at
        c_days=int(ts_diff.total_seconds() / (60*60*24))
    if updated_at is None:
        u_days = 999
    else:
        ts_diff=now_at-updated_at
        u_days=int(ts_diff.total_seconds() / (60*60*24))

    # print(db_name, c_days, u_days)

    if c_days < expire_days_created :
        print(db_name, "keep create",c_days,u_days, f_days)
        keep = keep + 1
        continue
    if u_days < expire_days_updated :
        print(db_name, "keep update",c_days,u_days, f_days)
        keep = keep + 1
        continue

    print(db_name, "expired",c_days,u_days, f_days)
    expired.append((db_name, "expired",c_days,u_days, f_days))

if cur2 is not False:
    try:
        cur2.close()
    except:
        pass
    cur2 = False
if conn2 is not False:
    try:
        conn2.close()
    except:
        pass
    conn2 = False

print('Scanned', scanned, 'Keep', keep, 'expire', len(expired))

if not dryrun :
    print("Here we go - going to delete", len(expired))
    time.sleep(5)

print()
actions = list()
first = True
for db in expired:
    if ( first ) : actions.append('Scanned '+str(scanned)+ ' Keep '+str(keep)+' expire '+str(len(expired)))
    first = False
    sql = 'DROP DATABASE '+db[0]+';'
    actions.append(sql+' '+db[1]+' -- create days='+str(db[2])+' update_days='+str(db[3]))
    if not dryrun:
        print(sql)
        time.sleep(1)
        try:
            stmt = cur.execute(sql)
        except Exception as e:
            actions.append(str(e))
            print(e)

cur.close()

# Send some email
if len(actions) > 0 :
    subject = "PG4E: Postgres Expire Actions ("+str(len(actions))+")"
    body = ''
    if dryrun: body = body + "This is a dry run\n\n"
    for action in actions:
        body = body + action + "\n"
    print(body)
    print(myutils.sendNotification(subject,body))

