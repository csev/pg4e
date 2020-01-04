
# Normalize the tax data

# python3 taxnorm.py

# SELECT v.id as id, regionid, ym, ym_val, city, state, metro, county FROM home_value AS v
# JOIN home_city ON city_id = home_city.id 
# JOIN home_state ON state_id = home_state.id
# JOIN home_metro ON metro_id = home_metro.id
# JOIN home_county ON county_id = home_county.id
# LIMIT 5;

import psycopg2
import hidden
import time
import myutils

# Load the secrets for the readwrite shared DB
secrets = hidden.readwrite()

conn = psycopg2.connect(host=secrets['host'],
        port=secrets['port'],
        database=secrets['database'],
        user=secrets['user'],
        password=secrets['pass'],
        connect_timeout=3)
incur = conn.cursor()
outcur = conn.cursor()

print('If you want to restart the process, run')
print('DROP TABLE IF EXISTS home_value CASCADE;')
print(' ')

# Note home_value already has a unique id from the raw data
sql = '''
CREATE TABLE IF NOT EXISTS home_city
(id SERIAL PRIMARY KEY, city VARCHAR(128) UNIQUE);

CREATE TABLE IF NOT EXISTS home_state
(id SERIAL PRIMARY KEY, state VARCHAR(128) UNIQUE);

CREATE TABLE IF NOT EXISTS home_county
(id SERIAL PRIMARY KEY, county VARCHAR(128) UNIQUE);

CREATE TABLE IF NOT EXISTS home_metro
(id SERIAL PRIMARY KEY, metro VARCHAR(128) UNIQUE);
'''
print(sql)
myutils.doQuery(outcur, sql)
conn.commit();

sql = '''
CREATE TABLE IF NOT EXISTS home_value
(id INTEGER UNIQUE, regionid INTEGER, ym DATE, ym_val INTEGER,
city_id INTEGER REFERENCES home_city(id) ON DELETE CASCADE, 
state_id INTEGER REFERENCES home_state(id) ON DELETE CASCADE, 
metro_id INTEGER REFERENCES home_metro(id) ON DELETE CASCADE, 
county_id INTEGER REFERENCES home_county(id) ON DELETE CASCADE
);
'''
print(sql)
myutils.doQuery(outcur, sql)
conn.commit();

# Cache the "lookup table info that we know"
cities = dict()
states = dict()
counties = dict()
metros = dict()

loaded = set()

# This is long process - it can be restarted
sql = 'SELECT id FROM home_value;'
print(sql)
incur.execute(sql)
while True:
    row = incur.fetchone()
    if row is None : break
    loaded.add(row[0])

print('Pre loaded', len(loaded))

print()
limit = input('LIMIT (0 means all):')
if ( len(limit) < 1 ) : quit()
limit = int(limit)

# This is long process - it can be restarted
sql = 'SELECT * FROM home_value_by_zip'
if limit > 0 : sql = sql + ' LIMIT ' + str(limit)
sql = sql + ';'
print(sql)
incur.execute(sql)

start = time.time()

# Lets see which ones we don't need to do

insert = 0
skip = 0
oldinsert = 0

print('Reading data...')
while True:
    row = incur.fetchone()
    if row is None: break

    # (1498337, 89955, 'Midwest City', 'OK', 'Oklahoma City', 'Oklahoma County', '1999-12', 81900)
    row_id = row[0]
    if row_id in loaded : 
        skip = skip + 1
        continue
    regionid = row[1]
    city = row[2]
    state = row[3]
    metro = row[4]
    county = row[5]
    ym = row[6] + '-01'
    ym_val = row[7]

    # Save some chatter by caching
    city_id = cities.get(city, None)
    state_id = states.get(state, None)
    county_id = counties.get(county, None)
    metro_id = metros.get(metro, None)

    # https://stackoverflow.com/questions/5247685/python-postgres-psycopg2-getting-id-of-row-just-inserted
    # https://stackoverflow.com/questions/46751530/postgresql-upsert-return-id-on-conflict

    # sql_string = "INSERT INTO domes_hundred (name,name_slug,status) VALUES (%s,%s,%s) RETURNING id;"
    # cursor.execute(sql_string, (hundred_name, hundred_slug, status))
    # hundred = cursor.fetchone()[0]

    if city_id is None:
        sql = "INSERT INTO home_city (city) VALUES (%s) ON CONFLICT (city) DO UPDATE set city = %s RETURNING id;"
        outcur.execute(sql, (city, city))
        city_id = outcur.fetchone()[0]
        cities[city] = city_id

    if state_id is None:
        sql = "INSERT INTO home_state (state) VALUES (%s) ON CONFLICT (state) DO UPDATE set state = %s RETURNING id;"
        outcur.execute(sql, (state, state))
        state_id = outcur.fetchone()[0]
        states[state] = state_id

    if metro_id is None:
        sql = "INSERT INTO home_metro (metro) VALUES (%s) ON CONFLICT (metro) DO UPDATE set metro = %s RETURNING id;"
        outcur.execute(sql, (metro, metro))
        metro_id = outcur.fetchone()[0]
        metros[metro] = metro_id

    if county_id is None:
        sql = "INSERT INTO home_county (county) VALUES (%s) ON CONFLICT (county) DO UPDATE set county = %s RETURNING id;"
        outcur.execute(sql, (county, county))
        county_id = outcur.fetchone()[0]
        counties[county] = county_id

    # (id INTEGER UNIQUE, regionid INTEGER, ym DATE,
    # city_id INTEGER REFERENCES home_city(id) ON DELETE CASCADE, 
    # state_id INTEGER REFERENCES home_state(id) ON DELETE CASCADE, 
    # metro_id INTEGER REFERENCES home_metro(id) ON DELETE CASCADE, 
    # county_id INTEGER REFERENCES home_county(id) ON DELETE CASCADE

    sql = '''INSERT INTO home_value (id,regionid,city_id,state_id,metro_id,county_id,ym,ym_val) 
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s) ON CONFLICT (id) DO NOTHING;'''

    outcur.execute(sql, (row_id, regionid, city_id, state_id, metro_id, county_id, ym, ym_val))
    loaded.add(row_id)

    insert = insert + 1;

    # Commit every 5 seconds
    now = time.time()
    diff = now - start
    if diff > 5 : 
        start = now
        conn.commit()
        rate = (insert - oldinsert ) / diff
        oldinsert = insert
        print('At', row_id, 'insert count=', insert, 'rate=', int(rate))
        continue

print(' ')
print(f'Skipped: {skip} Inserted: {insert}')
print('Closing database connection...')
conn.commit()
incur.close()
outcur.close()

