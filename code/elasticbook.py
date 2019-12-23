
# https://www.pg4e.com/code/loadbook.py
# https://www.pg4e.com/code/myutils.py

# Download a book
# wget http://www.gutenberg.org/cache/epub/19337/pg19337.txt

# (If needed)
# https://www.pg4e.com/code/hidden-dist.py
# copy hidden-dist.py to hidden.py
# edit hidden.py and put in your credentials

# python3 loadbook.py

from elasticsearch import Elasticsearch
import time
import copy
import hidden

bookfile = input("Enter book file (i.e. pg19337.txt): ")
if bookfile == '' : bookfile = 'pg19337.txt';
base = bookfile.split('.')[0]

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
res = es.indices.delete(index=base, ignore=[400, 404])
print("Dropped index", base)
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

res = es.indices.create(index=base, body=settings)
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
        doc = {
            'content': para
        }
        pcount = pcount + 1

        res = es.index(index=base, doc_type='paragraph', id=pcount, body=doc)
        # print('Added document...')
        # print(res['result'])

        if pcount % 100 == 0 : 
            print(pcount, 'loaded...')
            time.sleep(1)
            break
        para = ''
        continue

    para = para + ' ' + line

# Tell it to recompute the index
res = es.indices.refresh(index=base)
print("Index refreshed", base)
print(res)

print(' ')
print('Loaded',pcount,'paragraphs',count,'lines',chars,'characters')


# https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html

while True:
    search = input('Enter search term:')
    if len(search.strip()) < 1 : break
    res = es.search(index=base, body={"query": {"query_string": { "query": search, "default_field": "content" }}})

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

