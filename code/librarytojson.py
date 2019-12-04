
# https://www.pg4e.com/code/tracks.py

import xml.etree.ElementTree as ET
# https://docs.python.org/3/library/json.html
import json 

fname = input('Enter file name: ')
if ( len(fname) < 1 ) : fname = 'Library.xml'

# <key>Track ID</key><integer>369</integer>
# <key>Name</key><string>Another One Bites The Dust</string>
# <key>Artist</key><string>Queen</string>
def lookup(d, key):
    found = False
    for child in d:
        if found : return child.text
        if child.tag == 'key' and child.text == key :
            found = True
    return None

stuff = ET.parse(fname)
all = stuff.findall('dict/dict/dict')
print('Dict count:', len(all))

jsonfile = open('library.jstxt', 'w', newline='')

for entry in all:
    if ( lookup(entry, 'Track ID') is None ) : continue

    name = lookup(entry, 'Name')
    artist = lookup(entry, 'Artist')
    album = lookup(entry, 'Album')
    count = lookup(entry, 'Play Count')
    rating = lookup(entry, 'Rating')
    length = lookup(entry, 'Total Time')

    if name is None or artist is None or album is None : 
        continue

    if length is None : length = 0;
    if count is None : count = 0;
    if rating is None : rating = 0;

    entry = {'name': name, 'artist': artist, 'album': album,
            'count': int(count), 'rating': int(rating), 'length': int(length)}

    # Don't indent - we want this to be all one line
    jsonfile.write(json.dumps(entry))
    jsonfile.write('\n');

print("Results are in library.jstxt")
jsonfile.close()
