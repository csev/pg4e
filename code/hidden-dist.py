# Keep this file separate

# https://www.pg4e.com/code/hidden-dist.py

# psql -h pg.pg4e.com -p 5432 -U pg4e_be9e729093 pg4e_be9e729093

# %load_ext sql
# %config SqlMagic.autocommit=False
# %sql postgresql://pg4e_be9e729093:pg4e_p_d5fab7440699124@pg.pg4e.com:5432/pg4e_be9e729093
# %sql SELECT 1 as "Test"

def secrets():
    return {"host": "pg.pg4e.com",
            "port": 5432,
            "database": "pg4e_be9e729093",
            "user": "pg4e_be9e729093",
            "pass": "pg4e_p_d5fab7440699124"}

def elastic() :
    return {"host": "www.pg4e.com",
            "prefix" : "elasticsearch",
            "port": 443,
            "scheme": "https",
            "user": "pg4e_86f9be92a2",
            "pass": "2008_9d454b1f"}

def readonly():
    return {"host": "pg.pg4e.com",
            "port": 5432,
            "database": "readonly",
            "user": "readonly",
            "pass": "readonly_password"}

# Return a psycopg2 connection string

# import hidden
# secrets = hidden.readonly()
# sql_string = hidden.psycopg2(hidden.readonly())

# 'dbname=pg4e_data user=pg4e_data_read password=pg4e_p_d5fab7440699124 host=pg.pg4e.com port=5432'

def psycopg2(secrets) :
     return ('dbname='+secrets['database']+' user='+secrets['user']+
        ' password='+secrets['pass']+' host='+secrets['host']+
        ' port='+str(secrets['port']))

# Return an SQLAlchemy string

# import hidden
# secrets = hidden.readonly()
# sql_string = hidden.alchemy(hidden.readonly())

# postgresql://pg4e_data_read:pg4e_p_d5fab7440699124@pg.pg4e.com:5432/pg4e_data

def alchemy(secrets) :
    return ('postgresql://'+secrets['user']+':'+secrets['pass']+'@'+secrets['host']+
        ':'+str(secrets['port'])+'/'+secrets['database'])

