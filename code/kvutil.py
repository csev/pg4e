
# (If needed)
# https://www.pg4e.com/code/hidden-dist.py
# copy hidden-dist.py to hidden.py
# edit hidden.py and put in your url and token

# Install the requests library if you don't have it,
# These might work:
# Windows: python -m pip install requests
# MacOS: python3 -m pip install requests
# Linux: python -m pip install requests
# You also might want to install a virtual env (venv)
# and use pip to install requests in your virtual environment

import json

def prettyjson(status, text): 
    if status == 200:
        try: 
            print(json.dumps(json.loads(text), indent=2))
        except e:
            print(text)
    else :
        print(text)
        print()
        print("Error, status=", status)

def addtoken(url, secrets):
    queryurl = url + "?token=" + secrets['token']
    return queryurl

def readjson(prompt):
        print(prompt)
        inp = None
        text = ""
        while inp != "":
            inp = input().strip()
            text += inp
        return text

def parsejson(text):
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

