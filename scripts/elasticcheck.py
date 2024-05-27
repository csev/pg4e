# Some Python utility code for elasticsearch.
# uses the requests library (low level) rather than the Python elasticsearch wrapper

# https://www.pg4e.com/code/elastictool.py

# (If needed)
# https://www.pg4e.com/code/hidden-dist.py
# copy hidden-dist.py to hidden.py
# edit hidden.py and put in your credentials

import requests
import sys
import json
import hidden
import myutils

dryrun = True
if len(sys.argv) == 2 and sys.argv[1] == "delete" :
    print('This is the real deal - indexes WILL be deleted!')
    dryrun = False
else:
    print('This is a dry run add delete to actually delete the indices');

secrets = hidden.elastic()

max_quota = 2.5*1000*1000
# max_quota = 100000
max_days = 30

basepath = '/srv/lib/elasticsearch/nodes/0/indices/'
url = 'http://'+secrets['user']+':'+secrets['pass']+'@'+secrets['host']+':'+str(secrets['port'])
url = 'http://localhost:9200'

# https://www.elastic.co/guide/en/elasticsearch/reference/current/cat-indices.html
caturl = url + '/_cat/indices?format=json&pretty'
prurl = caturl.replace(secrets['pass'],'*****')

# Get the detail
response = requests.get(caturl)
text = response.text
# print('Debug', text)
status = response.status_code
js = json.loads(text)

# {'health': 'yellow', 'status': 'open', 'index': 'pg4e_302484828b', 'uuid': 'g1y44fpgSB2GRY8NPiG-nw',
# 'pri': '1', 'rep': '1', 'docs.count': '1', 'docs.deleted': '0', 'store.size': '5.2kb', 'pri.store.size': '5.2kb'}
indices = list()
for entry in js:
    size = myutils.getBytes(entry['store.size'])
    uuid = entry['uuid']
    # fpath = basepath + uuid 
    fpath = basepath + uuid + '/0/index'
    # print('fpath', fpath);
    days = myutils.mtime(fpath)
    index = entry['index']
    if not index.startswith('pg4e_') : continue
    # print(index, days, size);
    indices.append((index, days, size))

actions = list()

indices.sort(key = lambda indices: indices[2], reverse=True)
for index in indices:
    if index[2] > max_quota :
        queryurl = url + '/' + index[0]
        try:
            if not dryrun: response = requests.delete(queryurl)
            actions.append('Quota '+str(index[2])+' max='+str(max_quota)+' '+queryurl)
        except Exception as e:
            detail = str(e)
            actions.append('Quota FAIL '+str(index[2])+' max='+str(max_quota)+' '+queryurl+' '+detail)
        # break

indices.sort(key = lambda indices: indices[1], reverse=True)
for index in indices:
    if index[1] > max_days :
        queryurl = url + '/' + index[0]
        try:
            if not dryrun: response = requests.delete(queryurl)
            actions.append('Expire '+str(index[1])+' max='+str(max_days)+' '+queryurl)
        except Exception as e:
            detail = str(e)
            actions.append('Expire FAIL '+str(index[1])+' max='+str(max_days)+' '+queryurl+' '+detail)
        # break

# Send some email
if len(actions) > 0 :
    subject = "Subject: Elastic Search Actions ("+str(len(actions))+")"
    message = ''
    if dryrun: message = message + "This is a dry run\n\n";
    for action in actions:
        message = message + action + "\n";
    print(message)
    print(myutils.sendNotification(subject,message))


