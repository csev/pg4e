import string, textwrap

h = open('01-intro.mkd', 'r')
for line in h:
    line = line.strip()
    if len(line) < 1 : continue;
    if not line[0:1].isalpha() : continue;
    if line.find('  ') >= 0 : continue;
    if line == 'Exercises' : break;
    if line == 'Glossary' : break;
    line = line.translate(str.maketrans("","", string.punctuation))
    if line.find('stdin') >= 0 : continue;
    if line.find('www') >= 0 : continue;
    if line.find('Traceback') >= 0 : continue;
    if line.find('NameError') >= 0 : continue;
    pieces = line.split()
    if len(pieces) < 4 : continue
    line = textwrap.fill(line, 80)
    print(line)
