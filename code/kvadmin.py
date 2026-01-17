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
import socket

def urlerror(e, url=None, quit_on_error=False):
    """Handle URL errors with detailed diagnostics"""
    if isinstance(e, urllib.error.HTTPError):
        print()
        print('HTTP Error:', e.code, '-', e.reason)
        if url:
            print('URL:', url)
        if e.code == 404:
            print('Error: The endpoint was not found (404).')
            print('This might indicate the Deno service is running but the endpoint is missing.')
        elif e.code == 401:
            print('Error: Authentication failed (401).')
            print('You might have an incorrect token value')
            if url and '/dump' in url:
                print('Check your token in the hidden.py file.')
        elif e.code == 500:
            print('Error: Server error (500).')
            print('The Deno service is running but encountered an internal error.')
            print('The code in the server is failing somehow')
        elif e.code >= 500:
            print(f'Error: Server error ({e.code}).')
            print('The Deno service may be starting up or experiencing issues.')
        else:
            print(f'Error: Unexpected HTTP status code {e.code}.')
        try:
            error_body = e.read().decode('utf-8')
            if error_body:
                print('Server response:', error_body[:500])  # Limit to first 500 chars
        except:
            pass
    elif isinstance(e, urllib.error.URLError):
        print()
        print('URL Error:', str(e))
        if url:
            print('URL:', url)
        if isinstance(e.reason, socket.timeout):
            print('Error: Connection timed out after 30 seconds.')
            print('Socket timed out')
            print('This usually means:')
            print('  1. The Deno service is starting up but taking too long to respond')
            print('  2. The service is overloaded or unresponsive')
            print('  3. There is a network connectivity issue')
            print()
            print('The service might be coming up - try accessing the URL in a browser')
            print('and wait for it to respond, then restart kvadmin.')
        elif isinstance(e.reason, ConnectionRefusedError):
            print('Error: Connection refused.')
            print('This usually means:')
            print('  1. The Deno service is not running')
            print('  2. The service is running on a different port')
            print('  3. A firewall is blocking the connection')
        elif isinstance(e.reason, socket.gaierror):
            print('Error: DNS resolution failed.')
            print('This usually means:')
            print('  1. The hostname in the URL cannot be resolved')
            print('  2. There is a network connectivity issue')
            print('  3. The URL is incorrect')
        else:
            print('Error type:', type(e.reason).__name__)
            print('Error details:', str(e.reason))
            print('URL Error:', e.reason)
    elif isinstance(e, socket.timeout):
        print()
        print('Error: Socket timeout - the connection took longer than 30 seconds.')
        if url:
            print('URL:', url)
        print('This usually means the Deno service is starting up but not ready yet.')
        print('Try accessing the URL in a browser and wait for it to respond,')
        print('then restart kvadmin.')
    else:
        print()
        print('Unexpected error:', type(e).__name__, '-', str(e))
        if url:
            print('URL:', url)
        print('Unable to communicate with Deno.')
    
    if quit_on_error:
        print()
        print('Sometimes it takes a while to start the Deno instance after it has been idle.')
        print('You might want to access the url above in a browser, wait 30 seconds,')
        print('and then restart kvadmin.')
        print()
        quit()

def verifyconnection(url):
    """Verify connection to Deno service with detailed error reporting"""
    print('Verifying connection to', url)
    try:
        with urllib.request.urlopen(url, timeout=30) as response:
            text = response.read().decode('utf-8')
            status = response.status
            return True
    except Exception as e:
        urlerror(e, url=url, quit_on_error=True)

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

url = secrets['url'] + '/dump'
verifyconnection(url)

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
        try:
            with urllib.request.urlopen(req, timeout=30) as response:
                text = response.read().decode('utf-8')
                status = response.status
                prettyjson(status, text)
        except Exception as error:
            urlerror(error, url=url)
        continue

    # get /books/Hamlet
    # https://kv-admin-api.pg4e.com/kv/get/books/Hamlet?token=123

    # list /books
    # https://kv-admin-api.pg4e.com/kv/books?token=123

    if len(pieces) == 2 and (pieces[0] == 'get' or pieces[0] == 'list') :
        url = ( secrets['url'] + '/kv/' + pieces[0] + pieces[1] +
          '?token=' + secrets['token'] )
        if ( showurl ) : print(url)

        try:
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
        except Exception as error:
            urlerror(error, url=url)
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
        try:
            with urllib.request.urlopen(req, timeout=30) as response:
                text = response.read().decode('utf-8')
                status = response.status
                print('Status:', status)
                print(text)
        except Exception as error:
            urlerror(error, url=url)
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


