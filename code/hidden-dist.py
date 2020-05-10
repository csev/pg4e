# Keep this file separate

# https://www.pg4e.com/code/hidden-dist.py

# psql -h pg.pg4e.com -p 5432 -U pg4e_user_8187f pg4e

# %load_ext sql
# %config SqlMagic.autocommit=False
# %sql postgresql://pg4e_user_8187f:pg4e_pass_7f749@35.222.5.70:13009/pg4e
# %sql SELECT 1 as "Test"

def secrets():
    return {"host": "pg.pg4e.com",
            "port": 5432,
            "database": "pg4e_be9e729093",
            "user": "pg4e_be9e729093",
            "pass": "pg4e_p_d5fab7440699124"}

def elastic() :
    return {"host": "es.pg4e.com",
            "prefix" : "v1/basicauth/elasticsearch",
            "port": 9210,
            "scheme": "https",
            "user": "pg4e_86f9be92a2",
            "pass": "2008_9d454b1f"}

# Return a psycopg2 connection string

# import hidden
# secrets = hidden.readonly()
# sql_string = hidden.psycopg2(hidden.readonly())

# 'dbname=pg4e_data user=pg4e_data_read password=pg4e_pass_94e5d host=35.239.113.162 port=10001'

def psycopg2(secrets) :
     return ('dbname='+secrets['database']+' user='+secrets['user']+
        ' password='+secrets['pass']+' host='+secrets['host']+
        ' port='+str(secrets['port']))

# Return an SQLAlchemy string

# import hidden
# secrets = hidden.readonly()
# sql_string = hidden.alchemy(hidden.readonly())

# postgresql://pg4e_data_read:pg4e_pass_94e5d@35.239.113.162:10001/pg4e_data

def alchemy(secrets) :
    return ('postgresql://'+secrets['user']+':'+secrets['pass']+'@'+secrets['host']+
        ':'+str(secrets['port'])+'/'+secrets['database'])

