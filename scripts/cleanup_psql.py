
# Cleanup unused databases

# python3 cleanup_psql.py

import psycopg2
import os
import hidden
import time
import myutils
import sys

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
    now = time.time()
    db_folder = data_directory + '/base/' + str(db_oid)
    modified = os.path.getmtime(db_folder)
    folder_days = int((now - modified)/(60*60*24))

    # The folder's modification date is its creation date generally
    if folder_days < 60 : continue

    # Check the latest file modification date
    file_mod = None
    for dirpath, dnames, fnames in os.walk(db_folder):

        for fname in fnames:
            fpath = db_folder + '/' + fname
            modified = os.path.getmtime(fpath)
            mod_days = int((now - modified)/(60*60*24))
            if file_mod is None or mod_days < file_mod :
                file_mod = mod_days
            # print('   ', fpath, mod_days)

    # If files have been changing
    if file_mod < 30: continue
    print (db_name, db_folder, folder_days, file_mod)
    print( "last modified: %s" % time.ctime(os.path.getmtime(db_folder)))
    expired.append(db_name)

if dryrun :
    print("Dry run - this run would delete",len(expired))

    print()
    for db in expired:
        print('DROP DATABASE', db, ';')
    cur.close()
    quit()

print("Here we go - going to delete", len(expired))
time.sleep(5)

print()
for db in expired:
    sql = 'DROP DATABASE '+db+';'
    print(sql)
    time.sleep(1)
    stmt = cur.execute(sql)

cur.close()
