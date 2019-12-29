# https://www.pg4e.com/code/elastic0.py

# Example from:
# https://elasticsearch-py.readthedocs.io/en/master/

# pip3 install elasticsearch

# (If needed)
# https://www.pg4e.com/code/hidden-dist.py
# copy hidden-dist.py to hidden.py
# edit hidden.py and put in your credentials

from datetime import datetime
from elasticsearch import Elasticsearch

import hidden

secrets = hidden.elastic()

es = Elasticsearch(
    [secrets['host']],
    http_auth=(secrets['user'], secrets['pass']),
    scheme="http",
    port=secrets['port']
)

# Start fresh
# https://elasticsearch-py.readthedocs.io/en/master/api.html#indices
res = es.indices.delete(index='test-index', ignore=[400, 404])
print("Dropped index")
print(res)

# https://www.elastic.co/guide/en/elasticsearch/reference/current/properties.html
settings = {
        "mappings": {
            "tweet": {
                "properties": {
                    "author": {
                        "type": "keyword"
                    },
                    "text": {
                        "type": "text"
                    },
                    "timestamp": {
                        "type": "date"
                    },
                }
            }
        }
    }

res = es.indices.create(index='test-index', body=settings)
print("Created the index...")
print(res)

doc = {
    'author': 'kimchy',
    'text': 'Elasticsearch: cool. bonsai cool.',
    'timestamp': datetime.now(),
}

# Note - you can't change the key type after you start indexing documents
# res = es.index(index="test-index", id='abc', body=doc)
res = es.index(index="test-index", doc_type='tweet', id='abc', body=doc)
print('Added document...')
print(res['result'])

res = es.get(index="test-index", doc_type='tweet', id='abc')
print('Retrieved document...')
print(res)

# Tell it to recompute the index - normally it would take up to 30 seconds
# Refresh can be costly - we do it here for demo purposes
# https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-refresh.html
res = es.indices.refresh(index="test-index")
print("Index refreshed")
print(res)

# Read through all the documents...
# https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-all-query.html

# res = es.search(index="test-index", body={"query": {"match_all": {}}})

# Read the documents with a search term
# https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html

res = es.search(index="test-index", body={"query": {"match": { "text" : { "query": "bonsai" }}}})
print('Search results...')
print(res)
print("Got %d Hits:" % res['hits']['total'])
for hit in res['hits']['hits']:
    s = hit['_source']
    print(f"{s['timestamp']} {s['author']}: {s['text']}")


