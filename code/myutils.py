
def queryValue(cur, sql, fields=None, error=None) :
    row = queryRow(cur, sql, fields, error);
    if row is None : return None
    return row[0]

def queryRow(cur, sql, fields=None, error=None) :
    row = doQuery(cur, sql, fields)
    try:
        row = cur.fetchone()
        return row
    except Exception as e:
        if error: 
            print(error, e)
        else :
            print(e)
        return None

def doQuery(cur, sql, fields=None) :
    row = cur.execute(sql, fields)
    return row

def summary(cur) :
    total = queryValue(cur, 'SELECT COUNT(*) FROM swapi;')
    todo = queryValue(cur, 'SELECT COUNT(*) FROM swapi WHERE status IS NULL;')
    good = queryValue(cur, 'SELECT COUNT(*) FROM swapi WHERE status = 200;')
    error = queryValue(cur, 'SELECT COUNT(*) FROM swapi WHERE status != 200;')
    print(f'Total={total} todo={todo} good={good} error={error}')

