
# 
from datetime import datetime
from elasticsearch import Elasticsearch

import hidden

es = Elasticsearch()

secrets = hidden.elastic()

es = Elasticsearch(
    [secrets['host']],
    http_auth=(secrets['user'], secrets['pass']),
    scheme="http",
)

# Start fresh
# https://elasticsearch-py.readthedocs.io/en/master/api.html#indices

res = es.indices.delete(index='test-index', ignore=[400, 404])
print("Dropped index")
print(res)

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

# Tell it to recompute the index
res = es.indices.refresh(index="test-index")
print("Index refreshed")
print(res)

# Do a search
# res = es.search(index="test-index", body={"query": {"match_all": {}}})

# https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html

res = es.search(index="test-index", body={"query": {"match": { "text" : { "query": "bonsai" }}}})
print('Search results...')
print(res)
print("Got %d Hits:" % res['hits']['total'])
for hit in res['hits']['hits']:
    s = hit['_source']
    print(f"{s['timestamp']} {s['author']}: {s['text']}")


