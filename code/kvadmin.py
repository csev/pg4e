# A Python Deno KV Admin tool using web services

# Download these to a folder:

# https://www.pg4e.com/code/kvadmin.py
# https://www.pg4e.com/code/kvutil.py

# Follow the installation / configuration instructions in kvutil.py

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

    # set /books/Hamlet
    # https://kv-admin-api.pg4e.com/kv/set/books/Hamlet?token=123

    if len(pieces) == 2 and pieces[0] == 'set' :
    # delete_prefix /books
        prurl = secrets['url'] + '/kv/' + pieces[0] + pieces[1]
        print(prurl)
        queryurl = kvutil.addtoken(prurl, secrets)
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

    # get /books/Hamlet
    # https://kv-admin-api.pg4e.com/kv/get/books/Hamlet?token=123
    # 
    # list /books
    # https://kv-admin-api.pg4e.com/kv/books?token=123

    if len(pieces) == 2 and (pieces[0] == 'get' or pieces[0] == 'list') :
        prurl = secrets['url'] + '/kv/' + pieces[0] + pieces[1]
        print(prurl)
        queryurl = kvutil.addtoken(prurl, secrets)

        response = requests.get(queryurl)
        text = response.text
        status = response.status_code
        print(status)
        try:
            data = json.loads(text)
            pretty_json_string = json.dumps(data, indent=4)
            print(pretty_json_string)
        except Exception as e:
            print(text)
        continue

    # delete /books/Hamlet
    # https://kv-admin-api.pg4e.com/kv/delete/books/Hamlet?token=123

    # delete_prefix /books
    # https://kv-admin-api.pg4e.com/kv/delete_prefix/books?token=123

    if len(pieces) == 2 and (pieces[0] == 'delete' or pieces[0] == 'delete_prefix') :

        prurl = secrets['url'] + '/kv/' + pieces[0] + pieces[1]
        print(prurl)
        queryurl = kvutil.addtoken(prurl, secrets)
        response = requests.delete(queryurl)
        text = response.text
        status = response.status_code
        print('Status:', status)
        print(text)
        continue

    if len(pieces) == 1 and pieces[0] == 'samples' :
        print()
        print('{"author": "Bill", "title": "Hamlet", "isbn": "42", "lang": "ang"}')
        print('{"author": "Katie", "title": "Wizards", "isbn": "6848", "lang": "en"}')
        print('{"author": "Chuck", "title": "PY4E", "isbn": "8513", "lang": "en"}')
        print('{"author": "Kristen", "title": "PI", "isbn": "8162", "lang": "en"}')
        print('{"author": "James", "title": "Wisdom", "isbn": "3857", "lang": "en"}')
        print('{"author": "Barb", "title": "Mind", "isbn": "8110", "lang": "en"}')
        print('{"author": "Vittore", "title": "Tutti", "isbn": "1730", "lang": "es"}')
        print('{"author": "Chuck", "title": "Net", "isbn": "8151", "lang": "en"}')
        print()
        continue

    print()
    print('Invalid command, please try:')
    print('')
    print('  quit')
    print('  samples')
    print('  set /books/Hamlet')
    print('  get /books/Hamlet')
    print('  delete /books/Hamlet')
    print('  delete_prefix /books')

