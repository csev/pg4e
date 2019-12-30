
# https://www.pg4e.com/code/loadbook.py
# https://www.pg4e.com/code/myutils.py

# Download a book
# wget http://www.gutenberg.org/cache/epub/19337/pg19337.txt

# (If needed)
# https://www.pg4e.com/code/hidden-dist.py
# copy hidden-dist.py to hidden.py
# edit hidden.py and put in your credentials

# python3 loadbook.py

import psycopg2
import hidden
import time

bookfile = input("Enter book file (i.e. pg19337.txt): ")
if bookfile == '' : bookfile = 'pg19337.txt';
base = bookfile.split('.')[0]

# Make sure we can open the file
fhand = open(bookfile)

# Load the secrets
secrets = hidden.secrets()

conn = psycopg2.connect(host=secrets['host'], port=secrets['port'],
        database=secrets['database'], 
        user=secrets['user'], 
        password=secrets['pass'], 
        connect_timeout=3)

cur = conn.cursor()

sql = 'DROP TABLE IF EXISTS '+base+' CASCADE;'
print(sql)
cur.execute(sql)

sql = 'CREATE TABLE '+base+' (id SERIAL, body TEXT);'
print(sql)
cur.execute(sql)

para = ''
chars = 0
count = 0
pcount = 0
for line in fhand:
    count = count + 1
    line = line.strip()
    chars = chars + len(line)
    if line == '' and para == '' : continue
    if line == '' :
        sql = 'INSERT INTO '+base+' (body) VALUES (%s);'
        cur.execute(sql, (para, ))
        pcount = pcount + 1
        if pcount % 50 == 0 : conn.commit()
        if pcount % 100 == 0 : 
            print(pcount, 'loaded...')
            time.sleep(1)
        para = ''
        continue

    para = para + ' ' + line

conn.commit()
cur.close()

print(' ')
print('Loaded',pcount,'paragraphs',count,'lines',chars,'characters')

sql = "CREATE INDEX TABLE_gin ON TABLE USING gin(to_tsvector('english', body));"
sql = sql.replace('TABLE', base)
print(' ')
print('Run this manually to make your index:')
print(sql)


# SELECT body FROM pg19337  WHERE to_tsquery('english', 'goose') @@ to_tsvector('english', body) LIMIT 5;
# EXPLAIN ANALYZE SELECT body FROM pg19337  WHERE to_tsquery('english', 'goose') @@ to_tsvector('english', body);
