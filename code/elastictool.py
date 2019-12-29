# Some Python utility code for elasticsearch.
# uses the requests library (low level) rather than the Python elasticsearch wrapper

# https://www.pg4e.com/code/elastictool.py

# (If needed)
# https://www.pg4e.com/code/hidden-dist.py
# copy hidden-dist.py to hidden.py
# edit hidden.py and put in your credentials

import requests
import json
import hidden

secrets = hidden.elastic()

url = 'http://'+secrets['user']+':'+secrets['pass']+'@'+secrets['host']+':'+str(secrets['port']);

caturl = url + '/_cat/indices?format=json&pretty'
prurl = caturl.replace(secrets['pass'],'*****')
print(prurl)

while True:
    response = requests.get(caturl)
    text = response.text
    status = response.status_code
    js = json.loads(text)

    print('')
    print('Index / document count')
    print('----------------------')
    for entry in js:
        print(entry['index'], '/', entry['docs.count'])

    # print(text)
    print()
    try:
        cmd = input('Enter command: ').strip()
    except:
        print()
        break

    if cmd.startswith('quit') : break

    if cmd.startswith('detail') : 
        print(text)
        continue

    pieces = cmd.split()

    if len(pieces) == 2 and pieces[0] == 'delete' :
        if pieces[1] == 'searchguard' :
            print('')
            print("Don't do that...");
            continue

        queryurl = url + '/' + pieces[1]
        prurl = queryurl.replace(secrets['pass'],'*****')
        print(queryurl)
        response = requests.delete(queryurl)
        text = response.text
        status = response.status_code
        print('Status:', status)
        print(text)
        continue

    # https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-get-mapping.html
    if len(pieces) == 2 and pieces[0] == 'mapping' :
        queryurl = url + '/' + pieces[1] + '/_mapping?pretty'
        prurl = queryurl.replace(secrets['pass'],'*****')
        print(prurl)
        response = requests.get(queryurl)
        text = response.text
        status = response.status_code
        print(status)
        print(text)
        continue


    # https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-all-query.html
    if len(pieces) == 2 and pieces[0] == 'match_all' :
        queryurl = url + '/' + pieces[1] + '/_search?pretty'
        prurl = queryurl.replace(secrets['pass'],'*****')
        print(prurl)

        body=json.dumps( {"query": {"match_all": {}}} )

        hdict = {'Content-type': 'application/json; charset=UTF-8'}
        response = requests.post(queryurl, headers=hdict, data=body)
        text = response.text
        status = response.status_code
        print(status)
        print(text)
        continue

    if len(pieces) == 3 and pieces[0] == 'get' :
        queryurl = url + '/' + pieces[1] + '/' + pieces[2] + '?pretty'
        prurl = queryurl.replace(secrets['pass'],'*****')
        print(prurl)

        response = requests.get(queryurl)
        text = response.text
        status = response.status_code
        print(status)
        print(text)
        continue

    if len(pieces) == 3 and pieces[0] == 'search' :
        queryurl = url + '/' + pieces[1] + '/_search?pretty'
        prurl = queryurl.replace(secrets['pass'],'*****')
        print(prurl)

        body = json.dumps({ "query": {"query_string": {"query": pieces[2] }}})
        
        # {"query": {"query_string": { "query": search, "default_field": "content" }}}
        print(body)

        hdict = {'Content-type': 'application/json; charset=UTF-8'}
        response = requests.post(queryurl, headers=hdict, data=body)
        text = response.text
        status = response.status_code
        print(status)
        print(text)
        continue

    print()
    print('Invalid command, please try:')
    print('')
    print('  detail')
    print('  quit')
    print('  get indexname/doctype id')
    print('  search indexname/doctype string')
    print('  search indexname string')
    print('  mapping indexname')
    print('  match_all indexname')
    print('  delete indexname')

