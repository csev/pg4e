
# https://www.pg4e.com/code/elasticmail.py

# https://www.pg4e.com/code/hidden-dist.py
# copy hidden-dist.py to hidden.py
# edit hidden.py and put in your credentials

# python3 elasticmail.py
# Pulls data from the web and puts it into gmane index

import ssl
import urllib.request, urllib.parse, urllib.error
from urllib.parse import urljoin
from urllib.parse import urlparse
import re
import hidden
import myutils
import datecompat

from elasticsearch import Elasticsearch
import time
import json
import copy
import hidden

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

secrets = hidden.elastic()

# Connect to our database
es = Elasticsearch(
    [ secrets['host'] ],
    http_auth=(secrets['user'], secrets['pass']),
    scheme="http",
    port=secrets['port']
)


# https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html#built-in-date-formats
settings = {
        "mappings": {
            "message": {
                "properties": {
                    "body": {
                        "type": "text",
                        "analyzer" : "english"
                    },
                }
            }
        }
    }


try:
    res = es.indices.create(index='gmane', body=settings)
    print("Creating the gmane index if it is not there...")
    print(res)
except:
    print("The gmane index seems to already exist...")

baseurl = 'http://mbox.dr-chuck.net/sakai.devel/'

# Pick up where we left off
# https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-max-aggregation.html

agg = {
    "aggs" : {
        "max_id" : { "max" : { "field" : "id" } }
    }
}
query = json.dumps(agg)
res = es.search(index='gmane', body=agg)
start = res['aggregations']['max_id']['value']
if start is None : start = 0
print('start:', start)

many = 0
count = 0
fail = 0
while True:
    if ( many < 1 ) :
        sval = input('How many messages:')
        if ( len(sval) < 1 ) : break
        many = int(sval)

    start = start + 1

    # Skip rows that are already retrieved
    try:
        res = es.get(index='gmane', doc_type='message', id=start)
        print(res)
        continue;
    except: 
        pass

    many = many - 1
    url = baseurl + str(start) + '/' + str(start + 1)

    text = 'None'
    try:
        # Open with a timeout of 30 seconds
        document = urllib.request.urlopen(url, None, 30, context=ctx)
        text = document.read().decode()
        if document.getcode() != 200 :
            print('Error code=',document.getcode(), url)
            break
    except KeyboardInterrupt:
        print('')
        print('Program interrupted by user...')
        break
    except Exception as e:
        print('Unable to retrieve or parse page',url)
        print('Error',e)
        fail = fail + 1
        if fail > 5 : break
        continue

    print(url,len(text))
    count = count + 1

    if not text.startswith('From '):
        print(text)
        print('Did not find From ')
        fail = fail + 1
        if fail > 5 : break
        continue

    pos = text.find('\n\n')
    if pos > 0 :
        hdr = text[:pos]
        body = text[pos+2:]
    else:
        print(text)
        print('Could not find break between headers and body')
        fail = fail + 1
        if fail > 5 : break
        continue

    # Accept with or without < >
    email = None
    x = re.findall('\nFrom: .* <(\S+@\S+)>\n', hdr)
    if len(x) == 1 :
        email = x[0]
        email = email.strip().lower()
        email = email.replace('<','')
    else:
        x = re.findall('\nFrom: (\S+@\S+)\n', hdr)
        if len(x) == 1 :
            email = x[0]
            email = email.strip().lower()
            email = email.replace('<','')

    hdrlines = hdr.split('\n')
    hdrdict = dict()
    for line in hdrlines:
        # [('From', '"Glenn R. Golden" <ggolden@umich.edu>')]
        y = re.findall('([^ :]*): (.*)$', line)
        if len(y) != 1 : continue
        tup = y[0]
        if len(tup) != 2 : continue
        print(tup)
        key = tup[0].lower()
        value = tup[1].lower()
        hdrdict[key] = value

    print(hdrdict)
    # Reset the fail counter
    fail = 0
    print('   ',email)

    # cur.execute('''INSERT INTO Messages (id, email, sent_at, subject, headers, body)
    #    VALUES ( %s, %s, %s, %s, %s, %s ) ON CONFLICT DO NOTHING''',
    #           ( start, email, sent_at, subject, hdr, body))

    if count % 100 == 0 : time.sleep(1)

