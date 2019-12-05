<!DOCTYPE html>
<html lang="en">
    <head>
<title>PG4E - JSON - Lecture Notes</title>
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
"serialize",
"json",
"python",
"history",
"jsonb",
"swapi",
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
<h1 id="lecture">JSON Lecture Notes</h1>
<p>
JSON is a very common format for storing and transmitting structured data <em>between</em> programs.
</p>
<h2 id="serialize">Data Serialization
<?php doNav('serialize'); ?>
</h2>
<p>
Each programming language has ways of representing the two core types
of collections.
</p>
<p>
<table border="3px">
<thead>
<tr>
<th>Language</th>
<th>Linear Structure</th>
<th>Key / Value Structure</th>
</tr>
<thead>
<tbody>
<tr>
<td>Python</td>
<td>list() [1,2,3]</td>
<td>dict() {'a':1, 'b': 2}</td>
</tr>
<tr>
<td>JavaScript</td>
<td>Array [1,2,3]</td>
<td>Object {'a':1, 'b': 2}</td>
</tr>
<tr>
<td>PHP</td>
<td>Array array(1,2,3)</td>
<td>Array array('a' =&gt; 1, 'b' =&gt; 1)</td>
</tr>
<tr>
<td>Java</td>
<td>ArrayList</td>
<td>HashMap</td>
</tr>
</tbody>
</table>
</p>
<p>
In order to move structured data between applications, we need a "language independent" syntax to move the data.
If for example, we want to send a dictionary from Python to PHP we would take the following steps:
<ol>
<li>Within Python, we would convert the dictonary to this "independent format"
(<a href="https://en.wikipedia.org/wiki/Serialization" target="_blank">serialization</a>)
 and write it to a file.
<li>Within PHP we would read the file and convert it to an associative array
de-serialization).
</ol>
Another term for serialization and deserialization is
<a href="https://en.wikipedia.org/wiki/Marshalling_(computer_science)" target="_blank">
marshalling</a> and
<a href="https://en.wikipedia.org/wiki/Unmarshalling" target="_blank">
unmarshalling</a>.
</p>
<p>A long time ago....  We used XML as this "format to move data structures between various languages":
<pre>
&lt;array&gt;
    &lt;entry&gt;
       &lt;key&gt;a&lt;/key&gt;
       &lt;value&gt;1&lt;/value&gt;
    &lt;entry&gt;
    &lt;entry&gt;
       &lt;key&gt;b&lt;/key&gt;
       &lt;value&gt;2&lt;/value&gt;
    &lt;entry&gt;
&lt;/array&gt;
</pre>
</p>
<p>
XML (like HTML) is a good syntax to represent documents, but it is not a natural syntax to
represent lists or dictionaries.  We have been using XML as a way to represent structured data
for interchange since the 1990's. Before that we had serialization formats like
<a href="https://en.wikipedia.org/wiki/Abstract_Syntax_Notation_One" target="_blank">ASN.1</a>
fsince the mid-1980s.  And formats like Comma-Separated Values (CSV) work for linear structures
but not so much for keyvalue structures.
</p>
<p>
Around 2000, we started seeing the need to move structured data between code written in JavaScript
in browsers (front-end) and code running on the servers (back-end).  Initially the format of choice
was XML resulting in a programming pattern called
<a href="https://en.wikipedia.org/wiki/Ajax_(programming)" target="_blank">AJAX</a> - Asynchronous
JavaScript And XML</a>.   Many programming already had libraries to read and write XML syntax so
it was an obvious place to start.  And in the browser, XML looked a lot like HTML so it seemed
to make sense there as well.
</p>
<p>
The problem was that the structures we used in programs (list and key/value) were pretty inelegant
when expressed in XML, makeing the XML hard to read and a good bit of effort to convert.
</p>

<h2 id="json">JSON - JavaScript Object Notation
<?php doNav('json'); ?>
</h2>
<iframe width="400" height="240" src="https://www.youtube.com/embed/kc8BAR7SHJI" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="float:right;" alt="Video interview of Douglas Crockford"></iframe>
<p>
Given the shortcomings of XML to represent linear and key/value structures, as more and more applications,
started to transfer data between JavaScript on the browser and the databases on the back-end,
Douglas Crockford noticed that the syntax for JavaScript constants might be a good serialization
format.  In particular, JavaScript already understood the format natively:
<pre>
&lt;script type="text/javascript"&gt;
who = {
    "name": "Chuck",
    "age": 29,
    "college": true,
    "offices" : [ "3350DMC", "3437NQ" ],
    "skills" : { "fortran": 10, "C": 10,
        "C++": 5, "python" : 7 }
};
console.log(who);
&lt;/script&gt;
</pre>
It turned out to be easier to add libraries to the back-end languages like Python, PHP, and Java
to convert their data structures to JSON than to use XML to serialize data because the back-end
languages were already good at XML.  The reason was really because XML did a bad job of representing
linear or key/value structures that are widely used across all languages.
</p>
<p>
To help advance adoption in an industry that was (at the time) obsessed with XML,
Douglas Crockford wrote a simple specification for "JSON", and put it up at
<a href="https://www.json.org" target="_new">www.json.org</a> and programmers
started to use it in their software development.
</p>
<p>
In order to make parsing and generating JSON simpler, JSON required all of the keys of
key value pairs be surrounded by double quotes.
</p>
<p>
For those familiar with Python, JSON looks almost exactly like nested Python list and dictionary
constants.  And while Python was not so popular in 2001, now almost 20 years later,
with Python and JavaScript emerging as the most widely used languages, it makes
reading JSON pretty natural for those skilled in either language.
</p>
<p>
JSON has quickly become the dominant way to store and transfer data structures between programs.
JSON is sent across networks, stored on files, and stored in databases.  As JavaScript became an emerging server
language with the development of the
<a href="https://nodejs.org/en/" target="_blank">NodeJS</a> web server
and JSON specific databases like
<a href="https://www.mongodb.com/" target="_blank">MongoDB</a> were developed,
JSON is now used for all but a few data serialization use cases.  For those document-oriented
use cases like
<a href="https://en.wikipedia.org/wiki/Microsoft_Office_XML_formats" target="_blank">
Microsoft Office XML formats</a>, XML is still the superior solution.
</p>
<p>
Database systems like Oracle, SQLServer, PostgreSQL, and MySQL have been adding native JSON
columns to suport document-style storage in traditional relational databases.
</p>
<b>References</b>
<ul>
    <li>
<a href="https://www.youtube.com/watch?v=kc8BAR7SHJI" target="_blank">
    Interview with Douglas Crockford</a>
    </li>
</ul>

<h2 id="python">JSON in Python
<?php doNav('python'); ?>
</h2>
<p>In this section we will do a quick introduction of the
<a href="https://docs.python.org/3/library/json.html" target="_blank">
JSON library in Python</a>.
</p>
<p>
Using JSON in Python is very simple because JSON maps perfectly onto lists
and dictionaries.
<//p>
<p>
The
<a href="https://docs.python.org/3/library/json.html#json.dumps" target="_blank">
json.dumps()</a> library takes a python object and serializses it into JSON.
<pre>
<a href="../code/json1.py">https://www.pg4e.com/code/json1.py</a>

import json

data = {}
data['name'] = 'Chuck'
data['phone'] = {}
data['phone']['type'] = 'intl';
data['phone']['number'] = '+1 734 303 4456';
data['email'] = {}
data['email']['hide'] = 'yes'

# Serialize
print(json.dumps(data, indent=4))
</pre>
Produces the following output:
<pre>
{
    "name": "Chuck",
    "phone": {
        "type": "intl",
        "number": "+1 734 303 4456"
    },
    "email": {
        "hide": "yes"
    }
}
</pre>
</p>
<p>
The
<a href="https://docs.python.org/3/library/json.html#json.loads" target="_blank">
json.loads()</a> takes a string containing valid JSON and deserializes it
into a python dictionary or list as appropriate.
<pre>
<a href="../code/json2.py">https://www.pg4e.com/code/json2.py</a>

import json

data = '''
{
  "name" : "Chuck",
  "phone" : {
    "type" : "intl",
    "number" : "+1 734 303 4456"
   },
   "email" : {
     "hide" : "yes"
   }
}'''

info = json.loads(data)
print('Name:', info["name"])
print('Hide:', info["email"]["hide"])
</pre>
This code executes as follows:
<pre>
Name: Chuck
Hide: yes
</pre>
</p>
<p>
This also works with Python lists as well:
<ul>
<li>Serializing a list -
<a href="../code/json3.py">https://www.pg4e.com/code/json3.py</a>
</li>
<li>Deserializing a list -
<a href="../code/json4.py">https://www.pg4e.com/code/json4.py</a>
</li>
</ul>
</p>
<p>
Before we move on, here is a simple example of de-serializing XML
in Python similar to <b>json2.py</b> above:
<pre>
<a href="../code/xml1.py">https://www.pg4e.com/code/xml1.py</a>

import xml.etree.ElementTree as ET

data = '''
&lt;person&gt;
  &lt;name&gt;Chuck&lt;/name&gt;
  &lt;phone type="intl"&gt;
    +1 734 303 4456
  &lt;/phone&gt;
  &lt;email hide="yes" /&gt;
&lt;/person&gt;'''

tree = ET.fromstring(data)
print('Name:', tree.find('name').text)
print('Attr:', tree.find('email').get('hide'))
</pre>
Because XML is a tree based approach (neither a list nor a dictionary) we have to
use find <b>find()</b> function to query the tree, figure out its structure and
<i>hand transform</i> the data tree into our lists and/or dictionaries.  This is
the impedance mismatch between the "shape" of XML and the "shape" of data
structures inside programs that is mentioned by Douglas Crockford
in his interview above.
</p>
<p>
Again, it is importaint to point out that XML is a better than JSON when
representing things like hierarchical documents.  Also XML is a bit more verbose
and as such a bit more self-documenting as long as the XML tags
have reasonable names.









<h2 id="history">Structured Data in PostgreSQL
<?php doNav('history'); ?>
</h2>
<p>
A key to understanding JSON support in PostgreSQL is that it has evolved.  The great news is
that since you are probably using PostgreSQL 10 or later - we will only talk about the historical
perspective as history rather than having to use the old (somewhat clunky) support.
</p>
<p>
There are three supported column types in PostgreSQL that handle key/value or JSON data:
<ul>
<li>
<p>
<a href="https://www.postgresql.org/docs/12/hstore.html" target="_blank">
HSTORE</a> is column that can store keys and values.  It frankly looks like
a column that is a PHP Array / Python dictionary without support for 
nested data structures.
<pre>
pg4e=> SELECT 'a=>1,b=>2'::hstore;
       hstore
--------------------
 "a"=>"1", "b"=>"2"
(1 row)
</pre>
HSTORE stores key/value pairs efficiently and has good support for indexes
to allow <b>WHERE</b> clauses to look <em>inside</em> the column efficiently.
Indexes on HSTORE columns were easy to create and use (unlike the regular
expression based indexes we manually created in the
<a href="https://www.pg4e.com/code/gmane.py" target="_blank">gmane.py</a>
code).
</p>
</li>
<li>
<p>
<a href="https://www.postgresql.org/docs/9.3/functions-json.html" target="_blank">
JSON</a> (from PostgreSQL 9.3) is best thought of as a pre-release of JSONB.  
A <b>JSON</b> column was a glorified <b>TEXT</b> column with some really nifty built-in
functions that kept application developers from "hacking up" their own JSON-like
TEXT columns.  Things like JSON operators and functions were nicely carried
over into JSONB bring the best of JSON forward.  This "layer of functions and indexes"
on top of a TEXT column is a strategy that has been used by relational databases
to quckly build and release JSON support to counter the move to NoSQL databases
(more about that later).
</p></li>
<li>
<p>
<a href="https://www.postgresql.org/docs/current/functions-json.html" target="_blank">
JSONB</a> completely new column type that stores the parsed JSON densely
to save space, make indexing more effective, and make query / retrieval efficient.
The "B" stands for "better", but I like to think of it as "binary", ackmowledging that
it is no longer a big TEXT column that happens to contain a JSON string.
</p>
</li>
</ul>
<p>
In a sense, the JSONB support in PostgreSQL is a merger of the efficient storage
and indexing of the HSTORE merged with the rich operator
and function support of JSON.
</p>
<p>
But there are still some situations where HSTORE or JSON has an advantage over
JSONB - you can research that question online.  But for most aplications, 
just use JSONB even for simple key/value applications that might work well
with HSTORE.  It is less to remember and there will be a lot of investment
and performance tuning that goes into JSONB in future versions of PostgreSQL
as it competes with all the NoSQL databases.
</p>
<p>
<b>References</b>
<ul>
    <li>
<a href="https://www.citusdata.com/blog/2016/07/14/choosing-nosql-hstore-json-jsonb/" target="_blank">
    When to use unstructured datatypes in Postgres–Hstore vs. JSON vs. JSONB</a> (Blog Post)
    </li>
<li>
<a href="https://stackoverflow.com/questions/30800685/how-do-i-make-postgres-extension-available-to-non-superuser/59181721#59181721" target="_blank">
How do I make Postgres extension like hstore available to non superuser</a>
</li>
<li>
<a href="http://blog.shippable.com/why-we-moved-from-nosql-mongodb-to-postgressql" target="_blank">
Why we Moved From NoSQL MongoDB to PostgreSQL</a> (Blog Post)
</li>
<li>
<a href="https://www.linuxjournal.com/content/postgresql-nosql-database" target="_blank">
 PostgreSQL, the NoSQL Database
</a> (Linux Journal)
</li>
</ul>
</p>

<h2 id="jsonb">JSONB in PostgreSQL
<?php doNav('jsonb'); ?>
</h2>
<p>Now we <em>finally</em> get to talk about JSONB support in PostgreSQL. Like many of things
with PostgreSQL, the lead up / background is more complex to understand than the support
within PostgreSQL.
</p>
<p>
In this section, we will be going back to our music data except we will now be using
<a href="../code/library.jstxt" target="_blank">JSON data</a>.  If you are interested,
you can see the 
<a href="../code/librarytojson.py">Python code</a> 
which is used to convert the original 
<a href="../code/Library.xml" target="_blank">XML data</a> is 
converted to JSON format.
<pre>
{"name": "Another One Bites The Dust", "artist": "Queen", "album": "Greatest Hits", "count": 55, "rating": 100, "length": 217103}
{"name": "Beauty School Dropout", "artist": "Various", "album": "Grease", "count": 48, "rating": 100, "length": 239960}
{"name": "Circles", "artist": "Bryan Lee", "album": "Blues Is", "count": 54, "rating": 60, "length": 355369}
</pre>
</p>
<p>
Separately, we will go over a detailed walkthrough of
<a href="06-JSON.sql">SQL statements</a> using JSONB, so we will just show some of the highlights here.
</p>
<p>
We will create a table and import the above JSON file into the table as follows:
<pre>
CREATE TABLE IF NOT EXISTS jtrack (id SERIAL, body JSONB);

\copy jtrack (body) FROM 'library.jstxt' WITH CSV QUOTE E'\x01' DELIMITER E'\x02';
</pre>
The <b>\copy</b> command above is somewhat inelegant but it got our data in with a single
command.  In the 
<a href="#swapi">next section</a> of these notes, we will insert our JSON data using
Python which gives us a lot more flexibility.
</p>
<p>
When using JSONB it is important to know the types of data and use the cast (::) operator
where appropriate.  You can extract field data from the JSON using queries that use the "retrieve field
and convert from jsonb to text" operator (-&gt;&gt;).
<pre>
SELECT (body-&gt;&gt;'count')::int FROM jtrack WHERE body-&gt;&gt;'name' = 'Summer Nights';
</pre>
</p>
<p>
You can query JSONB fields by comparing them to other JSONB documents or document fragments
using the contains (@&gt;) operator.
<pre>
SELECT (body-&gt;&gt;'count')::int FROM jtrack WHERE body @&gt; '{"name": "Summer Nights"}';
</pre>
</p>
<p>
You can check to see if a JSONB document contains a key:
<pre>
SELECT COUNT(*) FROM jtrack WHERE body ? 'favorite';
</pre>
</p>
<p>
You can use JSONB expressions most anywhere you can use a column in your SQL,
making sure to cast the results where appropriate.
<pre>
SELECT body-&gt;&gt;'name' AS name FROM jtrack ORDER BY (body-&gt;&gt;'count')::int DESC;
</pre>
</p>
<h3>Indexes</h3>
<p>
Part of the benefit of using JSONB is the way you can easily add indexes to the whole column
or portions of the column using BTREE, HASH, Gin and other types of PostgresSQL indexes:
<pre>
CREATE INDEX jtrack_btree ON jtrack USING BTREE ((body-&gt;&gt;'name'));
CREATE INDEX jtrack_gin ON jtrack USING gin (body);
CREATE INDEX jtrack_gin_path_ops ON jtrack USING gin (body jsonb_path_ops);
</pre>
We will look at the kinds of <b>WHERE</b> clauses that make use of the 
various indexes.
</p>
<p>
<b>References</b>
<ul>
<li>
<a href="https://www.postgresql.org/docs/current/functions-json.html" target="_blank">
JSON Functions and Operators</a>
</li>
    <li>
<a href="https://bitnine.net/blog-postgresql/postgresql-internals-jsonb-type-and-its-indexes/" target="_blank">
    PostgreSQL internals: JSONB type and its indexes</a> (Blog Post)
    </li>
</li>
</ul>
</p>


<h2 id="swapi">Sample Code: Loading JSON from an API
<?php doNav('swapi'); ?>
</h2>
<p>
In this sample code walkthrough, we will use the
<a href="https://swapi.co/" target="_blank">Star Wars API</a>
to spider a JSON data source, and pull it into a database and then work
with the data using SQL.
</p>
and parse the contents of the book and put it into a PostgreSQL database and set up
a full-text GIN index on the book text.</p>
<p><b>Download these files</b></p>
<ul>
<li><a href="https://www.pg4e.com/code/swapi.py" target="_blank">
https://www.pg4e.com/code/swapi.py</a></li>
<li><a href="https://www.pg4e.com/code/myutils.py"  target="_blank">
https://www.pg4e.com/code/myutils.py</a></li>
</ul>
<p>Make sure the <b>hidden.py</b> is set up and has your credentials.</p>

<p>
The database for this program is more complex.  In addition to a column
for the JSON data we have fields to help make our data spidering process
"smart" so it only retrieves a particular document once.
<pre>
CREATE TABLE IF NOT EXISTS swapi (
  id SERIAL, body JSONB, 
  url VARCHAR(2048) UNIQUE, status INTEGER,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(), 
  updated_at TIMESTAMPTZ
);
</pre>
This application works somewhat like Google search because each time
it retrieves a JSON document, it scans the document for urls to other
documents available from the API, and adds these URLs to the database 
in a state of "to be retrieved".  The program runs over and over,
reading an unread URL, parsing the data, inserting it into the database
and checking for new URLs in the document and iterating.
</p>
<p>
If you run the program long enough, it finds all of the documents 
available in this API (turns out to be just over 200) and stops.
As you are only retrieving a few hundred documents, you 
probablly will not run into the 
<a href="https://swapi.co/documentation" target="_blank">
rate limit</a> of this API.  If you do you will have to wait a bit
and restart the program.
</p>
<p>
This is an example of the first run with an empty database:
<pre>
python3 swapi.py

INSERT INTO swapi (url) VALUES ( 'https://swapi.co/api/films/1/' )
INSERT INTO swapi (url) VALUES ( 'https://swapi.co/api/species/1/' )
INSERT INTO swapi (url) VALUES ( 'https://swapi.co/api/people/1/' )
Total=3 todo=3 good=0 error=0
How many documents:10
200 2201 https://swapi.co/api/films/1/ 2
200 1883 https://swapi.co/api/species/1/ 34
200 702 https://swapi.co/api/people/1/ 39
200 505 https://swapi.co/api/species/5/ 41
200 661 https://swapi.co/api/species/3/ 40
200 750 https://swapi.co/api/species/2/ 39
200 473 https://swapi.co/api/species/4/ 38
200 478 https://swapi.co/api/vehicles/4/ 37
200 433 https://swapi.co/api/vehicles/6/ 36
200 443 https://swapi.co/api/vehicles/7/ 35
How many documents:

Loaded 10 documents, 8529 characters
Total=45 todo=35 good=10 error=0
Closing database connection...
</pre>
At the end of this run, it has retrieved ten documents and has 35 documents
on the to-do list.
</p>
<p>
Any time during or after the run, you can use <b>psql</b>
in another window and check the progress of the job using commands
like:
<pre>
-- How many urls total?
SELECT COUNT(url) FROM swapi;

-- What are the unretrieved URLs?
SELECT url FROM swapi WHERE status != 200;
</pre>
<p>
Since it is a "spider" and restartable, you can run the
program again and it will pick up where it left off and
work on retrieving documents on the "to do list".
<pre>
python3 swapi.py

Total=45 todo=35 good=10 error=0
How many documents:5
200 524 https://swapi.co/api/vehicles/8/ 34
200 560 https://swapi.co/api/starships/2/ 33
200 574 https://swapi.co/api/starships/3/ 32
200 533 https://swapi.co/api/starships/5/ 31
200 581 https://swapi.co/api/starships/9/ 30
How many documents:

Loaded 5 documents, 2772 characters
Total=45 todo=30 good=15 error=0
Closing database connection...
</pre>
<p>
When you have all 200+ documents loaded, when you run the spider
it will just shut down because it has nothing on its to-do list.
</p>
<p>
At that point, we start playing with our retrieved JSON 
using SQL.
</p>

<br clear="all"/>
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
