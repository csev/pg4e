
import csv  # https://docs.python.org/3/library/csv.html

fhand = open('vehicles.csv')
reader = csv.reader(fhand)

first = True
data = set()
for row in reader:
    if first:
        first = False
        header = row
        print(header)
        make = header.index('make')
        model = header.index('model')
        continue
    # print(row[make], row[model])
    newrow = (row[make], row[model])
    if newrow in data: continue
    data.add(newrow)

data = sorted(data)

with open('makemodel.csv', 'w') as csvfile:
    spamwriter = csv.writer(csvfile)
    for tup in data:
       spamwriter.writerow(tup)
