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

doc = {
    'author': 'kimchy',
    'text': 'Elasticsearch: cool. bonsai cool.',
    'timestamp': datetime.now(),
}

# res = es.index(index="test-index", doc_type='tweet', id=1, body=doc)
# print(res['result'])

res = es.get(index="test-index", doc_type='tweet', id=1)

es.indices.refresh(index="test-index")

res = es.search(index="test-index", body={"query": {"match_all": {}}})
print("Got %d Hits:" % res['hits']['total'])
for hit in res['hits']['hits']:
    s = hit['_source']
    print(f"{s['timestamp']} {s['author']}: {s['text']}")


