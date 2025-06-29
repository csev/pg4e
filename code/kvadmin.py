# Some Python utility code for deno kv.

# https://www.pg4e.com/code/kvadmin.py

# (If needed)
# https://www.pg4e.com/code/hidden-dist.py
# copy hidden-dist.py to hidden.py
# edit hidden.py and put in your credentials

import requests
import json
import hidden
import kvutil

secrets = hidden.denokv()

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
    if len(pieces) == 2 and (pieces[0] == 'delete' or pieces[0] == 'delete_prefix') :

        prurl = secrets['url'] + '/kv/' + pieces[0] + pieces[1]
        print(prurl)
        queryurl = kvutil.addkey(prurl, secrets)
        response = requests.delete(queryurl)
        text = response.text
        status = response.status_code
        print('Status:', status)
        print(text)
        continue

    # https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-all-query.html
    if len(pieces) == 2 and pieces[0] == 'set' :
        print()
        print('{"title":"Bob Was Way Fun", "author":"William Shakespeare", "year":1600}');
        print()
        prurl = secrets['url'] + '/kv/' + pieces[0] + pieces[1]
        print(prurl)
        queryurl = kvutil.addkey(prurl, secrets)
        text = kvutil.readjson("Enter json (finish with a blank line:")
        data = kvutil.parsejson(text)
        if data == None : continue

        body=json.dumps(data, indent=2)
        hdict = {'Content-type': 'application/json; charset=UTF-8'}
        response = requests.post(queryurl, headers=hdict, data=body)

        text = response.text
        status = response.status_code
        kvutil.prettyjson(status, text)
        continue

    if len(pieces) == 2 and (pieces[0] == 'get' or pieces[0] == 'list') :
        prurl = secrets['url'] + '/kv/' + pieces[0] + pieces[1]
        print(prurl)
        queryurl = kvutil.addkey(prurl, secrets)

        response = requests.get(queryurl)
        text = response.text
        status = response.status_code
        print(status)
        try:
            data = json.loads(text)
            pretty_json_string = json.dumps(data, indent=4)
            print(pretty_json_string)
        except e:
            print(text)
        continue

    print()
    print('Invalid command, please try:')
    print('')
    print('  quit')
    print('  set /books/Hamlet')
    print('  get /books/Hamlet')
    print('  delete /books/Hamlet')
    print('  delete')

