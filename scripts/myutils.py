import smtplib, ssl
import hidden
import os
import time

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

def sendMail(message) :
    secrets = hidden.email()
    if not secrets.get('account') : 
        print('Account not in hidden.email()')
        return

    sender_email = secrets['account']
    receiver_email = secrets['account']

    # Create a secure SSL context
    context = ssl.create_default_context()
    context.check_hostname = False
    context.verify_mode = ssl.CERT_NONE

    with smtplib.SMTP_SSL(secrets["server"], secrets["port"], context=context) as server:
        server.login(secrets["account"], secrets["password"])
        server.sendmail(sender_email, receiver_email, message)

def getBytes(size):
    if size.endswith("kb"):
        size = float(size.rstrip("kb")) * 1000
    elif size.endswith("mb"):
        size = float(size.rstrip("mb")) * 1000 * 1000
    elif size.endswith("gb"):
        size = float(size.rstrip("gb")) * 1000 * 1000 * 1000
    elif size.endswith("b"):
        size = int(size.rstrip("b"))
    else :
        size = -1
    return size

def mtime(db_folder):
    now = time.time()
    file_mod = None
    mod_days = -1
    count = 0
    for dirpath, dnames, fnames in os.walk(db_folder):

        for fname in fnames:
            fpath = dirpath + '/' + fname
            modified = os.path.getmtime(fpath)
            mod_days = int((now - modified)/(60*60*24))
            if file_mod is None or mod_days < file_mod :
                file_mod = mod_days
            # print('   ', fpath, mod_days)
            count = count + 1
    return mod_days
