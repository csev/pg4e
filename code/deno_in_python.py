
deno = dict()

deno[('books', 'Wizards up Late')] = {'author': 'Katie', 'title': 'Wizards', 'isbn': '6848', 'lang': 'en'}
deno[('authors', 'Katie Hafner')] = {'author': 'Katie', 'title': 'Wizards', 'isbn': '6848', 'lang': 'en'}
deno[('books', 'Python')] = {'author': 'Chuck', 'title': 'PY4E', 'isbn': '8513', 'lang': 'en'}
deno[('authors', 'Charles Severance')] = {'author': 'Chuck', 'title': 'PY4E', 'isbn': '8513', 'lang': 'en'}
deno[('books', 'Raspberry PI')] = {'author': 'Kristen', 'title': 'PI', 'isbn': '8162', 'lang': 'en'}
deno[('authors', 'Kristen Fontichairo')] = {'author': 'Kristen', 'title': 'PI', 'isbn': '8162', 'lang': 'en'}
deno[('books', 'Wisdom of Crowds')] = {'author': 'James', 'title': 'Wisdom', 'isbn': '3857', 'lang': 'en'}
deno[('authors', 'James Surowiecki')] = {'author': 'James', 'title': 'Wisdom', 'isbn': '3857', 'lang': 'en'}
deno[('books', 'Mindshift')] = {'author': 'Barb', 'title': 'Mind', 'isbn': '8110', 'lang': 'en'}
deno[('authors', 'Barb Oakley')] = {'author': 'Barb', 'title': 'Mind', 'isbn': '8110', 'lang': 'en'}
deno[('books', 'Python per tutti')] = {'author': 'Vittore', 'title': 'Tutti', 'isbn': '1730', 'lang': 'es'}
deno[('authors', 'Vittori Zen')] = {'author': 'Vittore', 'title': 'Tutti', 'isbn': '1730', 'lang': 'es'}
deno[('authors', 'Charles Severance')] = {'author': 'Chuck', 'title': 'Net', 'isbn': '8151', 'lang': 'en'}
deno[('books', 'Networking Intro')] = {'author': 'Chuck', 'title': 'Net', 'isbn': '8151', 'lang': 'en'}


for key in deno:
    print(key, '=>', deno[key])
print()

for key in sorted(deno):
    print(key, '=>', deno[key])

for key, value in deno.items():
    print(key, '=>', value)
print()

for key in sorted(deno):
    keystr = ''
    for keypart in key:
        keystr = keystr + '/' + keypart
    print(keystr.ljust(30,' '), '=>', deno[key])

