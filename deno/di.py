
deno = dict()

deno[('books', 'Wizards up Late')] = 'Katie Hafner';
deno[('authors', 'Katie Hafner')] = 'Wizards up Late';
deno[('books', 'Python')] = 'Charles Severance';
deno[('authors', 'Charles Severance')] = 'Python';
deno[('books', 'Raspberry PI')] = 'Kristen Fontichairo';
deno[('authors', 'Kristen Fontichairo')] = 'Raspberry PI';
deno[('books', 'Wisdom of Crowds')] = 'James Surowiecki';
deno[('authors', 'James Surowiecki')] = 'Wisdom of Crowds';
deno[('books', 'Mindshift')] = 'Barb Oakley';
deno[('authors', 'Barb Oakley')] = 'Midshift';

for key, value in deno.items():
    print(key, '=>', value)
print()

# Sort by keys - pretend to be deno
deno = dict(sorted(deno.items()))

for key, value in deno.items():
    print(key, '=>', value)

