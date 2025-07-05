# A Python Deno KV Admin tool using web services

# Download these to a folder:

# https://www.pg4e.com/code/kvadmin.py
# https://www.pg4e.com/code/hidden-dist.py (If needed)

# (If needed)
# copy hidden-dist.py to hidden.py
# edit hidden.py and put in your url and token

import urllib.request
import urllib.parse
import urllib.error
import json
import hidden

def prettyjson(status, text):
    """Pretty print JSON response with status code handling"""
    if status == 200:
        try:
            print(json.dumps(json.loads(text), indent=2))
        except Exception as e:
            print(text)
    else :
        print(text)
        print()
        print("Error, status=", status)

def addtoken(url, secrets):
    """Add authentication token to URL"""
    queryurl = url + "?token=" + secrets['token']
    return queryurl

def readjson(prompt):
    """Read multi-line JSON input from user"""
    print(prompt)
    inp = None
    text = ""
    while inp != "":
        inp = input().strip()
        text += inp
    return text

def parsejson(text):
    """Parse JSON text with error handling"""
    try :
        data = json.loads(text)
        return data
    except json.JSONDecodeError as e:
        print(f"Error decoding JSON: {e}")
        print(f"Error message: {e.msg}")
        print(f"Error position: {e.pos}")
        print(f"Error line number: {e.lineno}")
    except Exception as e:
        print(f"An unexpected error occurred: {e}")

    return None

# Main program logic

secrets = hidden.denokv()
showurl = True


url = secrets['url'] + '/dump';
print('Verifying connection to', url)
try:
    with urllib.request.urlopen(url, timeout=30) as response:
        text = response.read().decode('utf-8')
        status = response.status
except Exception as e:
    print()
    print('Unable to communicate with server.  Sometimes it takes a while to start the')
    print('server after it has been idle.  You might want to access this url in a browser')
    print('and then restart kvadmin.');
    print()
    print(url)
    print()

while True:

    print()
    try:
        cmd = input('Enter command: ').strip()
    except KeyboardInterrupt:
        print()
        break
    except EOFError:
        print()
        break

    if cmd.startswith('quit') : break

    pieces = cmd.split(' ', 2)

    # set /books/Hamlet
    # set /books/Hamlet {}
    # https://kv-admin-api.pg4e.com/kv/set/books/Hamlet?token=123

    if len(pieces) >= 2 and pieces[0] == 'set' :
        url = (secrets['url'] + '/kv/set' + pieces[1]
          + '?token=' + secrets['token'] )
        if len(pieces) == 3 :
            text = pieces[2]
        else:
            text = readjson("Enter json (finish with a blank line:")
        data = parsejson(text)
        if data == None : continue

        body = json.dumps(data, indent=2).encode('utf-8')
        headers = {'Content-type': 'application/json; charset=UTF-8'}

        if ( showurl ) : print(url)

        req = urllib.request.Request(url, data=body, headers=headers, method='POST')
        with urllib.request.urlopen(req, timeout=30) as response:
            text = response.read().decode('utf-8')
            status = response.status
            prettyjson(status, text)
        continue

    # get /books/Hamlet
    # https://kv-admin-api.pg4e.com/kv/get/books/Hamlet?token=123

    # list /books
    # https://kv-admin-api.pg4e.com/kv/books?token=123

    if len(pieces) == 2 and (pieces[0] == 'get' or pieces[0] == 'list') :
        url = ( secrets['url'] + '/kv/' + pieces[0] + pieces[1] +
          '?token=' + secrets['token'] )
        if ( showurl ) : print(url)

        with urllib.request.urlopen(url, timeout=30) as response:
            text = response.read().decode('utf-8')
            status = response.status
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

        url = ( secrets['url'] + '/kv/' + pieces[0] + pieces[1] +
          '?token=' + secrets['token'] )

        if ( showurl ) : print(url)

        req = urllib.request.Request(url, method='DELETE')
        with urllib.request.urlopen(req, timeout=30) as response:
            text = response.read().decode('utf-8')
            status = response.status
            print('Status:', status)
            print(text)
        continue

    if len(pieces) == 1 and pieces[0] == 'show' :
        showurl = True
        continue

    if len(pieces) == 1 and pieces[0] == 'hide' :
        showurl = False
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
    print('  list /books')
    print('  delete /books/Hamlet')
    print('  delete_prefix /books')


