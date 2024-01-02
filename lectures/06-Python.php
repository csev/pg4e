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
"connect",
"loadbook",
"gmane",
"ranking",
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
<p><b>Additional Materials</b>
<ul>
<li><a href="06-Python.sql" target="_blank">Sample SQL commands for this lecture</a></li>
<li>URL for these notes: <a href="https://www.pg4e.com/lectures/06-Python" target="_blank">
https://www.pg4e.com/lectures/06-Python</a></li>
</ul>
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
It is important to note that Python is just another client like psql or pgadmin.
It makes a network connection to the database server using login credentials and
sends SQL commands and receives results from the server.
</p>
<p>
In order to connect to 
<pre>
$ python3
Python 3.6.0 (v3.6.0:41df79263a11, Dec 22 2016, 17:23:13)
&gt;&gt;&gt; import psycopg2
&gt;&gt;&gt;
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
<h2 id="connect">SQL Commands in Python
<?php doNav('connect'); ?>
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
program. You can think of the cursor as the equivalent of the "psql" command prompt
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
<?php doNav('loadbook'); ?>
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
You can watch the progress of the load using <b>psql</b> in another window:
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
we are flushing the connection using a <b>conn.commit()</b> every 50 inserts.
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

<h2 id="gmane">Sample Code: Loading Email Data
<?php doNav('gmane'); ?>
</h2>
<p>
In this example, we download some historical email data and do some parsing and cleanup
of the data and insert it into a table.  Then we use regular expressions to make an index
that looks deep into the text field to allow indexed searches on data within the field.
</p>
<p>
This example shows some of the real-world challenges you will find when you have 
a historical data source that is not "perfect" in its formatting or approach and
you must make use of the data regardless of its lack of consistency.  As you look
at it you quickly can see that Python was the only way to clean up this data.
</p>
<p><b>Download these files</b></p>
<ul>
<li><a href="https://www.pg4e.com/code/gmane.py" target="_blank">
https://www.pg4e.com/code/gmane.py</a></li>
<li><a href="https://www.pg4e.com/code/myutils.py"  target="_blank">
https://www.pg4e.com/code/myutils.py</a></li>
<li><a href="https://www.pg4e.com/code/datecompat.py"  target="_blank">
https://www.pg4e.com/code/datecompat.py</a></li>
</ul>
<p>Make sure the <b>hidden.py</b> is set up as above and has your credentials.
The <b>datecompat.py</b> is needed because certain needed date parsing / conversion
routines are only available in later versions of Python and is there to allow the code
to run across many versions of Python.
</p>
<p>
The code creates a table to store the email messages:
<pre>
CREATE TABLE IF NOT EXISTS messages
    (id SERIAL, email TEXT, sent_at TIMESTAMPTZ,
     subject TEXT, headers TEXT, body TEXT)
</pre>
And then retrieves email messages from a copy of a message archive at
<a href="http://mbox.dr-chuck.net/sakai.devel/" target="_blank">
http://mbox.dr-chuck.net/sakai.devel/</a>.  It turns out that email
data is particularly wonky because so many different products send,
receive, and process email and they treat certain fields ever so slightly
differently.
</p>
<p>
The mail messages are in a format called 
<a href="https://en.wikipedia.org/wiki/Mbox" target="_blank">Mbox</a>.
This format is a flat file where each message starts with a line
"From ," followed by a set of headers, followed by one blank line,
followed by the actual message text.
<pre>
<a href="http://mbox.dr-chuck.net/sakai.devel/4/6" target="_blank">http://mbox.dr-chuck.net/sakai.devel/4/6</a>

<b>From news@gmane.org Tue Mar 04 03:33:20 2003</b>
From: "Feldstein, Michael" &lt;Michael.Feldstein@suny.edu&gt;
Subject: RE: LMS/VLE rants/comments
Date: Fri, 09 Dec 2005 09:43:12 -0500

Yup, I think this is spot-on. Either/or, in reality, is actually neither
and both. We've had many discussions internally at SUNY about just how
loose our coupling can be. You start ...

<b>From news@gmane.org Tue Mar 04 03:33:20 2003</b>
From: John Norman &lt;john@caret.cam.ac.uk&gt;
Subject: RE: LMS/VLE rants/comments
Date: Fri, 9 Dec 2005 13:32:29 -0000

I should chip in here as Dan's PHB :)

Our strategy at Cambridge is to try and get the best of both worlds. I am
dismayed by the either/or tone of many discussions...
</pre>
You can look through the output and see both how simple and how complex
the mail messages can be.  And how much fun we were having in the 
<a href="https://www.sakailms.org" target="_blank">
Sakai Project</a> developing an Open Source Learning Management
System in 2005.
</p>
<p>
This application is retrieving data across a slow network, talking to possibly
overloaded servers with a possibility of
<a href="https://en.wikipedia.org/wiki/Rate_limiting" target="_blank">
Rate Limits</a> on those servers.  So we use a strategy similar to 
<a href="https://en.wikipedia.org/wiki/Web_crawler" target="_blank">
Web Crawlers</a> where we make a restartable process that can be aborted
part-way through and then next time the application runs, it picks up
where it left off.
</p>
<p>
Like a web crawler, our goal is to make a complete copy of the data in our
fast database, and clean it up and so we can repeatedly do very fast
data analysis on our copy of the data.
</p>
<p>
This is a complex bit of sample code and took more than a week of trial and
error to develop, so as you look at this code, don't feel like somehow
every trick and technique to clean the data, recover form errors or build
a restartable process is something you just write from scratch and it works
perfectly the first time.  
</p>
<p>Code like this evolves based on your data analysis needs and the vagaries
of your data and data source.  You start to build code, run it and when it
fails, you adapt and improvise.  Eventually you transform the raw data into
a pretty form in the database so the rest of your analysis works smoothly.
</p>


<h2 id="ranking">Ranking The Results
<?php doNav('ranking'); ?>
</h2>
<p>
The key benefit of a GIN / Natural Language index is to speed up the look up and retrieval
of the rows selected in the <b>WHERE</b> clause.  When we have a set of rows, what we do with
those rows is relatively inexpensive.
</p>
<p>
Ranking of the "how well" a row (ts_vector) matches the query (ts_query) is something we compute
directly from the data in the <b>ts_query</b> and the <b>ts_vector</b> in each row.  We can use different fields
in the ranking computation than the fields we use in the <b>WHERE</b> clause.   The <b>WHERE</b> clause dominates
the cost of a query as it decides how to gather the matching rows.
<pre>
SELECT id, subject, sender,
  ts_rank(to_tsvector('english', body), to_tsquery('english', 'personal &amp; learning')) as ts_rank
FROM messages
WHERE to_tsquery('english', 'personal &amp; learning') @@ to_tsvector('english', body)
ORDER BY ts_rank DESC;

 id |          subject           |           sender           | ts_rank
----+----------------------------+----------------------------+----------
  4 | re: lms/vle rants/comments | Michael.Feldstein@suny.edu | 0.282352
  5 | re: lms/vle rants/comments | john@caret.cam.ac.uk       |  0.09149
  7 | re: lms/vle rants/comments | john@caret.cam.ac.uk       |  0.09149

SELECT id, subject, sender,
  ts_rank_cd(to_tsvector('english', body), to_tsquery('english', 'personal &amp; learning')) as ts_rank
FROM messages
WHERE to_tsquery('english', 'personal &amp; learning') @@ to_tsvector('english', body)
ORDER BY ts_rank DESC;

 id |          subject           |           sender           |  ts_rank
----+----------------------------+----------------------------+-----------
  4 | re: lms/vle rants/comments | Michael.Feldstein@suny.edu |  0.130951
  5 | re: lms/vle rants/comments | john@caret.cam.ac.uk       | 0.0218605
  7 | re: lms/vle rants/comments | john@caret.cam.ac.uk       | 0.0218605
</pre>
</p>
<p>
There are two at least two ranking functions <b>ts_rank</b> and <b>ts_rank_cd</b>.  There is also
the ability to weight different elements of a <b>ts_query</b> that influence how the relative ranking
is computed.
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
