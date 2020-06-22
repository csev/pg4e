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

import warnings
warnings.filterwarnings('ignore', message='Unverified HTTPS request')

secrets = hidden.elastic()

url = 'http://'
if secrets['scheme'] == 'https' : 
    url = 'https://'
url = url+secrets['user']+':'+secrets['pass']+'@'+secrets['host']+':'+str(secrets['port']);
if secrets.get('prefix') : 
    url = url + '/' + secrets['prefix']
url = url + '/' + secrets['user']

while True:

    print()
    try:
        cmd = input('Enter command: ').strip()
    except:
        print()
        break

    if cmd.startswith('quit') : break

    pieces = cmd.split()

    # https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-delete-index.html
    if len(pieces) == 1 and pieces[0] == 'delete' :

        prurl = url.replace(secrets['pass'],'*****')
        print(prurl)
        response = requests.delete(url)
        text = response.text
        status = response.status_code
        print('Status:', status)
        print(text)
        continue

    # https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-all-query.html
    if len(pieces) == 1 and pieces[0] == 'match_all' :
        queryurl = url + '/_search'
        prurl = queryurl.replace(secrets['pass'],'*****')
        print(prurl)

        body=json.dumps( {"query": {"match_all": {}}} )

        hdict = {'Content-type': 'application/json; charset=UTF-8'}
        response = requests.post(queryurl, verify=False, headers=hdict, data=body)
        text = response.text
        status = response.status_code
        print(status)
        print(text)
        continue

    # https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-get.html
    if len(pieces) == 2 and pieces[0] == 'get' :
        queryurl = url + '/_doc/' + pieces[1] + '?pretty'
        prurl = queryurl.replace(secrets['pass'],'*****')
        print(prurl)

        response = requests.get(queryurl, verify=False)
        text = response.text
        status = response.status_code
        print(status)
        print(text)
        continue

    # https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
    if len(pieces) == 2 and pieces[0] == 'search' :
        queryurl = url + '/_search?pretty'
        prurl = queryurl.replace(secrets['pass'],'*****')
        print(prurl)

        body = json.dumps({ "query": {"query_string": {"query": pieces[1] }}})
        
        # {"query": {"query_string": { "query": search, "default_field": "content" }}}
        print(body)

        hdict = {'Content-type': 'application/json; charset=UTF-8'}
        response = requests.post(queryurl, verify=False, headers=hdict, data=body)
        text = response.text
        status = response.status_code
        if status == 200:
            print(status)
            print(json.dumps(json.loads(text), indent=2))
        else :
            print(text)
            print()
            print("Error, status=", status)
        continue

    print()
    print('Invalid command, please try:')
    print('')
    print('  quit')
    print('  get id')
    print('  search string')
    print('  match_all')
    print('  delete')

