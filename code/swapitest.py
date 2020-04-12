import urllib.request, urllib.parse, urllib.error
import json
import ssl

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

base = "https://swapi.py4e.com/api/"

resources = {
        "films" : [7, "title"],
        "people" : [87, "name"],
        "planets" : [61, "name"],
        "species" : [37, "name"],
        "starships" : [36, "name"],
        "vehicles" : [39, "name"],
}

# Known missing
known = ['people/17/', 'starships/1/', 'starships/14/', 'starships/16/', 'starships/18/', 'starships/19/', 'starships/20/', 'starships/24/', 'starships/25/', 'starships/26/', 'starships/30/', 'starships/33/', 'starships/34/', 'starships/35/', 'starships/36/', 'starships/4/', 'starships/6/', 'starships/7/', 'starships/8/', 'vehicles/1/', 'vehicles/10/', 'vehicles/11/', 'vehicles/12/', 'vehicles/13/', 'vehicles/15/', 'vehicles/17/', 'vehicles/2/', 'vehicles/21/', 'vehicles/22/', 'vehicles/23/', 'vehicles/27/', 'vehicles/28/', 'vehicles/29/', 'vehicles/3/', 'vehicles/31/', 'vehicles/32/', 'vehicles/39/', 'vehicles/5/', 'vehicles/9/']

# Uncomment this to see all the missing
# known = []

fail = []
for k, v in resources.items():
    print('Starting', k)
    for i in range(v[0]) :
        access = k + '/' + str(i+1) + '/'
        if access in known : continue
        url = base + access
        try :
            fhand = urllib.request.urlopen(url, context=ctx)
        except Exception as e:
            print(url, e)
            fail.append(access)
            continue
        data = fhand.read().decode()
        info = json.loads(data)
        field = v[1]
        field = info.get(field, False)
        if not field :
            fail.append(access)
            print(url)
            print(len(data))
            print(data)

fail.sort()
print('Unexpected missing endpoints:')
print(fail)
