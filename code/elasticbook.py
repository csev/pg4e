
# https://www.pg4e.com/code/elasticbook.py

# Download a book
# wget http://www.gutenberg.org/cache/epub/18866/pg18866.txt
# wget http://www.gutenberg.org/cache/epub/14091/pg14091.txt
# wget https://www.gutenberg.org/files/2591/2591-0.txt
# wget https://www.gutenberg.org/files/11/11-0.txt

# (If needed)
# https://www.pg4e.com/code/hidden-dist.py
# copy hidden-dist.py to hidden.py
# edit hidden.py and put in your credentials

# python3 elasticbook.py

from elasticsearch import Elasticsearch
import time
import copy
import hidden
import uuid

bookfile = input("Enter book file (i.e. pg18866.txt): ")
if bookfile == '' : bookfile = 'pg18866.txt';
indexname = bookfile.split('.')[0]

# Make sure we can open the file
fhand = open(bookfile)

# Load the secrets
secrets = hidden.elastic()

es = Elasticsearch(
    [ secrets['host'] ],
    http_auth=(secrets['user'], secrets['pass']),
    scheme="http",
    port=secrets['port']
)

# Start fresh
# https://elasticsearch-py.readthedocs.io/en/master/api.html#indices
res = es.indices.delete(index=indexname, ignore=[400, 404])
print("Dropped index", indexname)
print(res)

# https://www.elastic.co/guide/en/elasticsearch/reference/current/properties.html
# https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis.html
settings = {
        "mappings": {
            "paragraph": {
                "properties": {
                    "content": {
                        "type": "text",
                        "analyzer" : "english"
                    },
                }
            }
        }
    }

res = es.indices.create(index=indexname, body=settings)
print("Created the index...")
print(res)

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
        pcount = pcount + 1
        doc = {
            'offset' : pcount,
            'content': para
        }

        # Use a GUID for the primary key
        pkey = uuid.uuid4()
        res = es.index(index=indexname, doc_type='paragraph', id=pkey, body=doc)

        # print('Added document...')
        # print(res['result'])

        if pcount % 100 == 0 : 
            print(pcount, 'loaded...')
            time.sleep(1)

        para = ''
        continue

    para = para + ' ' + line

# Tell it to recompute the index
res = es.indices.refresh(index=indexname)
print("Index refreshed", indexname)
print(res)

print(' ')
print('Loaded',pcount,'paragraphs',count,'lines',chars,'characters')


# https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html

while True:
    search = input('Enter search term:')
    if len(search.strip()) < 1 : break
    res = es.search(index=indexname, body={"query": {"query_string": { "query": search, "default_field": "content" }}})

    summary = copy.deepcopy(res)   # Make a copy for printing
    del(summary['hits']['hits'])   # delete the detail from the copy
    print('Search results...')
    print(summary)
    print()

    print("Got %d Hits:" % res['hits']['total'])
    for hit in res['hits']['hits']:
        s = hit['_source']
        print(f"{s['content']}")
        print()

