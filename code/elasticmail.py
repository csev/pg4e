
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

import dateutil.parser as parser # If this import fails - just comment it out

def parsemaildate(md) :
    try:
        pdate = parser.parse(tdate)
        test_at = pdate.isoformat()
        return test_at
    except:
        return datecompat.parsemaildate(md)

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
                    "offset": {
                        "type": "long"
                    },
                    "headers.date": {
                        "type": "date"
                    },
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
        "max_id" : { "max" : { "field" : "offset" } }
    }
}
query = json.dumps(agg)
res = es.search(index='gmane', body=agg)
# print(res)
start = res['aggregations']['max_id']['value']
if start is None : start = 0
start = int(start)
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

    # Hack the date
    sent_at = None
    y = re.findall('\nDate: .*, (.*)\n', hdr)
    if len(y) == 1 :
        tdate = y[0]
        tdate = tdate[:26]
        try:
            sent_at = parsemaildate(tdate)
        except:
            print(text)
            print('Parse fail',tdate)
            fail = fail + 1
            if fail > 5 : break
            continue

    # Make the headers into a dictionary
    hdrlines = hdr.split('\n')
    hdrdict = dict()
    for line in hdrlines:
        # [('From', '"Glenn R. Golden" <ggolden@umich.edu>')]
        y = re.findall('([^ :]*): (.*)$', line)
        if len(y) != 1 : continue
        tup = y[0]
        if len(tup) != 2 : continue
        # print(tup)
        key = tup[0].lower()
        value = tup[1].lower()
        hdrdict[key] = value

    # Override the date field
    hdrdict['date'] = sent_at

    # Reset the fail counter
    fail = 0
    doc = {'offset': start, 'sender': email, 'headers' : hdrdict, 'body': body}
    res = es.index(index='gmane', doc_type='message', id=start, body=doc)
    print('   ',start, email, sent_at)
    # print('Added document...')
    # print(res['result'])

    if count % 100 == 0 : time.sleep(1)

