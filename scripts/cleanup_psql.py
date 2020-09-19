
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

expired = list()
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

print("Dry run - this run would delete",len(expired))
cur.close()

print()
for db in expired:
    print('DROP DATABASE', db, ';');
