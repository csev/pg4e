<!DOCTYPE html>
<html lang="en">
    <head>
<title>PG4E - Python and PostgreSQL - Lecture Notes</title>
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
<h1 id="lecture">PostgreSQL / Python Lecture Notes</h1>
<p>
In this lecture we will continue to look at ways to store complex data in PostgreSQL,
create indexes on that data and then use the data.  We will look at how we connect to 
a PostgreSQL database from within Python.
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
    Python-PostgreSQL Database Adapter</a>
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

<h2 id="loadbook">Sample Code: Loading The Text of a Book
<?php doNav('psycopg2'); ?>
</h2>
<p>
In this sample code walkthrough, we will download the text of a book from 
<a href="https://www.gutenberg.org/" target="_blank">Project Gutenberg</a>
and parse the contents of the book and put it into a PostgreSQL database and set up
a full-text GIN index on the book text.</p>
<p><b>Download these files</b></p>
<ul>
<li><a href="https://www.pg4e.com/code/loadbook.py" target="_blank">
https://www.pg4e.com/code/loadbook.py</a></li>
<li><a href="https://www.pg4e.com/code/myutils.py"  target="_blank">
https://www.pg4e.com/code/myutils.py</a></li>
</ul>
<p>Make sure the <b>hidden.py</b> is set up as above and has your credentials.</p>
<p>Download a book from the Gutenberg project using <b>wget</b> or <b>curl</b>:</p>
<pre>
wget http://www.gutenberg.org/cache/epub/19337/pg19337.txt
</pre>
Then run the loadbook code:
<pre>
python3 loadbook.py
</pre>
The program makes a document database, reads through the book text,
breaking it into "paragraphs" and inserting each paragraph into a row
of the database.  The table is automatically <em>created by the code</em>
and named the same as the book file so you can have more than one
book in your database at one time.
<pre>
CREATE TABLE pg19337 (id SERIAL, body TEXT);
</pre>
<p>
You can watch the progress of the load using <b>pgsql</b> in another window:
<pre>
pg4e=&gt; select count(*) from pg19337;
 count
-------
    50
(1 row)

pg4e=&gt; select count(*) from pg19337;
 count
-------
   150
(1 row)
</pre>
You will notice that it always is a multiple of 50 until the load finishes because
we are flushing the connection using a <b>con.commit()</b> every 50 inserts.
</p>
<p>
Once the load is complete, you will create the <b>GIN</b> index and play with some queries:
<pre>
CREATE INDEX pg19337_gin ON pg19337 USING gin(to_tsvector('english', body));

EXPLAIN ANALYZE SELECT body FROM pg19337  WHERE to_tsquery('english', 'goose') @@ to_tsvector('english', body);

                                                     QUERY PLAN
---------------------------------------------------------------------------------------------------------------------
 Bitmap Heap Scan on pg19337  (cost=12.03..24.46 rows=4 width=225) (actual time=0.027..0.029 rows=6 loops=1)
   Recheck Cond: ('''goos'''::tsquery @@ to_tsvector('english'::regconfig, body))
   Heap Blocks: exact=1
   -&gt;  Bitmap Index Scan on pg19337_gin  (cost=0.00..12.03 rows=4 width=0) (actual time=0.016..0.016 rows=6 loops=1)
         Index Cond: ('''goos'''::tsquery @@ to_tsvector('english'::regconfig, body))
 Planning Time: 0.523 ms
 Execution Time: 0.070 ms
</pre>


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
