<!DOCTYPE html>
<html lang="en">
    <head>
<title>PG4E - Python and JSON - Lecture Notes</title>
<style>
body {
    font-family: sans-serif;
}
.headerlink {
    text-decoration: none;
}
</style>
<?php
$sections = array(
"psycopg2",
"python-connect",
);

function doNav($position) {
    global $sections;
    $pos = array_search($position, $sections);
    if ( $pos === FALSE ) return;
    if ( $pos > 0 ) {
        echo('<a class="headerlink" href="#'.$sections[$pos-1].'" title="Previous Section">⏪</a>'."\n");
    }
    echo('<a class="headerlink" href="#'.$sections[$pos].'" title="Link to this Section">📖</a>'."\n");
    echo('<a class="headerlink" href="#lecture" title="Go to the beginning of the document">🏠</a>'."\n");
    if ( $pos < count($sections)-1 ) {
        echo('<a class="headerlink" href="#'.$sections[$pos+1].'" title="Next Section">⏩</a>'."\n");
    }
}
?>
    </head>
    <body>
<h1 id="lecture">PostgreSQL / Python / JSON Lecture Notes</h1>
<p>
In this lecture we will continue to look at ways to store complex data in PostgreSQL,
create indexes on that data and then use the data.  We will look at how we connect to 
a PostgreSQL database from within Python Python.
</p>

<h2 id="psycopg2">Connecting Python and PostgreSQL
<?php doNav('psycopg2'); ?>
</h2>
<p>
While we can do a lot of data importing, processing and exporting, some problems are 
most simply solved by writing a bit of Python.  Python can communicate with databases
very naturally.  In a sense, you use all the SQL skills that you have been learning but
construct the SQL statements as strings in Python and send them to your PostgreSQL
server.
</p>
<p>
It is important to note that Python is just another client like pgsql or pgadmin.
It makes a network connection to the database server using login credentials and
sends SQL commands and receives results from the server.
</p>
<p>
In order to connect to 
<pre>
$ python3
Python 3.6.0 (v3.6.0:41df79263a11, Dec 22 2016, 17:23:13)
>>> import psycopg2
>>>
</pre>
If the <b>import</b> fails you need to install the library using a command like:
<pre>
pip install psycopg2      # or pip3
</pre>
Pip is only one of many ways to manage Python "dependencies" / "add ons" depending
on your operating system / virtual environment / python installation pattern.
</p>
<p><b>References</b></p>
<p>
<ul>
    <li>
<a href="https://pypi.org/project/psycopg2/" target="_blank">
    Python-PostgreSQL Database Adapter</a> (Blog Post)
    </li>
</ul>
</p>
<h2 id="python-connect">SQL Commands in Python
<?php doNav('python-connect'); ?>
</h2>
<p>
In this section we will be talking about 
<a href="../code/simple.py" target="_blank">simple.py</a>
and
<a href="../code/hidden-dist.py" target="_blank">hidden-dist.py</a>.
</p>
<p>
The sequence in Python to connect and log in to a PostgreSQL database is:
<pre>
import psycopg2

conn = psycopg2.connect(
    host='35.123.23.37', database='pg4e',
    user='pg4e_user_42', password='pg4e_pass_42',
    connect_timeout=3)
</pre>
The connection makes ure that your account and password are correct and
there is truly a PostgreSQL server running on the specified host.  If you notice
in the code for 
<a href="../code/simple.py" target="_blank">simple.py</a>, we store the actual secrets
in a file called <b>hidden.py</b> and import them.   You make your
<b>hidden.py</b> file by copying 
<a href="../code/hidden-dist.py" target="_blank">hidden-dist.py</a>
and putting in your host, user, password, and database values.
</p>
<p>
Normally, we don't actually send SQL commands using the <b>connection</b>. For that
we generally get a <b>cursor</b>.  The cursor allows us to send an SQL command and then
retrieve the results, perhaps in a loop. We can ue the cursor over and over in our
program. You can think of the cursor as the equivalent of the "pgsql" command prompt
but inside your Python program. You can have more than one cursor open at a time.
<pre>
cur = conn.cursor()
cur.execute('DROP TABLE IF EXISTS pythonfun CASCADE;')

...
cur.execute('SELECT id, line FROM pythonfun WHERE id=5;')
row = cur.fetchone()
print('Found', row)
</pre>
In the above example <b>cur</b> is just a commonly used variable name for a
database cursor.
</p>
<p>
In the above example, when you send a <b>SELECT</b> using <b>cur.execute()</b>
it does not retrieve the data.  It primes the cursor to retrieve the data
using methods like <b>fetchone()</b>.
</p>
<p><b>References</b></p>
<p>
<ul>
    <li>
<a href="https://www.python.org/dev/peps/pep-0249/" target="_blank">
    PEP 249 -- Python Database API Specification v2.0
</a> 
    </li>
</ul>
</p>

<footer style="margin-top: 50px;">
<hr/>
<p>
Copyright 
<a href="https://www.dr-chuck.com/" target="_blank">
Charles R. Severance</a>, CC0 - 
You are welcome to adapt, reuse or reference this material with or without attribution.
</p>
<p>
Feel free to help improve this lecture at 
<a href="https://www.pg4e.com/lectures/06-PythonJSON.php" target="_blank">GitHub</a>.
</p>
</footer>
