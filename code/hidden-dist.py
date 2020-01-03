# Keep this file separate

# psql -h 35.239.113.162 -p 13009 -U pg4e_user_8187f pg4e

# %load_ext sql
# %config SqlMagic.autocommit=False
# %sql postgresql://pg4e_user_8187f:pg4e_pass_7f749@35.222.5.70:13009/pg4e
# %sql SELECT 1 as "Test"

def secrets():
    return {"host": "35.222.5.70",
            "port": 13009,
            "user": "pg4e_user_8187f",
            "pass": "pg4e_pass_7f749",
            "database": "pg4e"}

def elastic():
    return {"host": "35.239.113.162",
            "port": 13009,
            "user": "admin",
            "pass": "sakaiger"}

def readonly():
    return {"host": "35.239.113.162",
            "port": 10014,
            "user": "pg4e_data_read",
            "pass": "pg4e_pass_9876d",
            "database": "pg4e_data"}


