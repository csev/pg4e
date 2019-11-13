<html>
    <head>
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
"data-layout",
"indexes",
"index-types",
"like-google",
"invert-sql",
"inverted",
"stemming",
"text-search",
"natural",
"ranking",
"list-index"
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
<h1 id="lecture">PostgreSQL Full Text Lecture Notes</h1>
<p>
In this lecture we will explore how PostgreSQL indexes work and how we build indexes
for large text fields that contain natural language
and how we can look into those fields and use indexes to search large text fields efficiently.
</p>
<h2 id="data-layout">Row Data Layout
<?php doNav('data-layout'); ?>
</h2>
<p>
Rows can vary quite a bit in terms of length.
<pre>
CREATE TABLE messages
 (id SERIAL,              -- 4 bytes
  email TEXT,             -- 10-20 bytes
  sent_at TIMESTAMPTZ,    -- 8 bytes
  subject TEXT,           -- 10-100 bytes
  headers TEXT,           -- 500-1000 bytes
  body TEXT               -- 50-2000 bytes
                          -- 600-2500 bytes
);
</pre>
<p>
Since modifying data is so important to databases, we don't pack store one row after another in a file.
We arrange the file into blocks (default 8K) and pack the rows into blocks leaving some free space
to make inserts updates, or deletes possible without needing to rewrite a large file to move
things up or down.
</p>
<div style="float: right; width: 50%; min-width: 250;"><a href="05-FullText-images/postgres-disk-blocks.png" target="_blank">
<img src="05-FullText-images/postgres-disk-blocks.png" style="border: 2px solid black; width: 100%;">
</a></div>
<p>
PostgreSQL Organizes Rows into Blocks
<ul>
    <li>We read an entire block into memory (i.e. not just one row)</li>
    <li>Easy to compute the start of a block within a file for random access</li>
    <li>There are the unit of caching in memory</li>
    <li>They are (often) the unit of locking when we think we are locking a row</li>
</ul>
</p>
<p>
What is the Best Block Size?
<ul>
    <li>Blocks that are small waste free space / fragmentation</li>
    <li>Large blocks take more memory in cache be cached for a given memory size</li>
    <li>Large blocks longer to read and write to/from SSD </li>
</ul>
<p>
If we have a table that contains 1GB (125,000 blocks) of data, a sequential scan from a
fast SSD takes about 2 seconds while with careful optimization, reading a random block
can be fast as 1/50000th of a second.  Some SSD drives can read as many as 32 different
random blocks in a single read request.  If the block is already cached in memory it is even
faster.  Sequential scans are very bad.
</p>

<b>References</b>
<ul>
    <li>
<a href="https://malisper.me/the-file-layout-of-postgres-tables/" target="_blank">
    The File Layout of Postgres Tables</a> (Blog Post)
    </li>
    <li>
        <a href="http://rachbelaid.com/introduction-to-postgres-physical-storage/" target="_blank">
            Introduction to PostgreSQL physical storage
        </a> (Blog Post)
    </li>
    <li><a href="https://blog.pgaddict.com/posts/postgresql-on-ssd-4kb-or-8kB-pages" target="_blank">
            PostgreSQL on SSD - 4kB or 8kB pages?
        </a> (Blog Post)
    </li>
</ul>
<br clear="all"/>
<h2 id="indexes">Indexes
<?php doNav('indexes'); ?>
</h2>
<p>
Assume each row in the <b>users</b> table is about 1K, we could save a lot of time if somehow we had a hint
about which row was in which block.
<pre>
email              | block
-------------------+------
anthony@umich.edu  | 20175
csev@umich.edu     | 14242
colleen@umich.edu  | 21456

SELECT name FROM users WHERE email='csev@umich.edu';
SELECT name FROM users WHERE email='colleen@umich.edu';
SELECT name FROM users WHERE email='anthony@umich.edu';
</pre>
Our index would be about 30 bytes per row which is much smaller than the actual row data.  We store index data in 8K
blocks as well - as indexes grow in size we need to find was to avoid reading the entire index to look up one key.
We need an index to the index.  For string logical keys, a B-Tree index is a good, general solution.
B-Trees keep the keys in sorted order by reorganizing the tree as keys are inserted.
</p>
<p>
PostgreSQL Index Types
<ul>
    <li>B-Tree - The default for many applications - automatically balanced as it grows</li>
    <li>BRIN - Block Range Index - Smaller / faster if data is mostly sorted</li>
    <li>Hash - Quick lookup of long key strings</li>
    <li>GIN - Generalized Inverted Indexes - multiple values in a column</li>
    <li>GiST - Generalized Search Tree</li>
    <li>SP-GiST - Space Partitioned Generalized Search Tree</li>
</ul>
</p>
<b>References</b>
<ul>
    <li><a href="http://www.postgresqltutorial.com/postgresql-indexes/postgresql-index-types/" target="_blank">
            PostgreSQL Index Types</a> (Blog Post)
    </li>
    <li><a href="https://en.wikipedia.org/wiki/B-tree" target="_blank">
            B-Tree Index</a> (WikiPedia)
    </li>
    <li><a href="https://en.wikipedia.org/wiki/Block_Range_Index" target="_blank">
            Block Range Index</a> (WikiPedia)
    </li>
</ul>

<h2 id="index-types">Forward and Inverted Indexes
<?php doNav('index-types'); ?>
</h2>
<p>
It is not a perfect metaphor but in general there are two categories of indexes:
<ul>
    <li><b>Forward indexes</b> - You give the index a logical key and it tells you where to find the row
        that contains the key. (B-Tree, BRIN, Hash)</li>
    <li><b>Inverse indexes</b> - You give the index a string (query) and the index gives you a list of <i>all</i>
        the rows that match the query. (GIN, GiST)</li>
</ul>
The metaphor is not perfect - because B-tree indexes are stored in sorted order, if you give
a B-Tree the prefix of a logical key, it can give you a set of rows...
</p><p>
The most typical use case for an <b>inverse index</b> is to quickly search text documents wit one or a few words.
</p>
<b>References</b>
<ul>
    <li>
<a href="https://en.wikipedia.org/wiki/Search_engine_indexing#Inverted_indices" target="_blank">
    Inverted_indices</a> (Wikipedia)</li>
</ul>
<h2 id="like-google">
    Similar to Google Search
<?php doNav('like-google'); ?>
</h2>
<ul>
    <li>Crawl: Retrieve documents, parse them and create an <b>inverted index</b></li>
    <li>Search: Take keywords, find the documents with the words then rank them and present results</li>
</ul>
<b>References</b>
<ul>
    <li><a href="https://youtu.be/6x0cAzQ7PVs?t=559" traget="_blank">
            Google I/O '08 Keynote by Marissa Mayer</a></li>
    <li><a href="https://www.youtube.com/watch?v=BNHR6IQJGZs" target="_blank">
            How Search Works - Matt Cutts</a></li>
</ul>

<h2 id="invert-sql">Inverted Indexes - The Hard Way
<?php doNav('invert-sql'); ?>
</h2>
<div style="float: right; width: 25%; min-width: 250;"><a href="05-FullText-images/inverted-index.png" target="_blank">
<img src="05-FullText-images/inverted-index.png" style="border: 2px solid black; width: 100%;">
</a></div>
<p>We can aplit long text columns into space-delimited words using PostgreSQL's split-like function
called <b>string_to_array()</b>.  And then we can use the PostgresSQL <b>unnest()</b> function to turn
the resulting array into separate rows.
<pre>
pg4e=&gt; string_to_array('Hello world', ' ');
 string_to_array
-----------------
 {Hello,world}

pg4e=&gt; unnest(string_to_array('Hello world', ' '));
 unnest
--------
 Hello
 world
</pre>
After that, it is just a few <b>SELECT DISTINCT</b> statements and we can create and use an inverted index.
<pre>
CREATE TABLE docs (id SERIAL, doc TEXT, PRIMARY KEY(id));
INSERT INTO docs (doc) VALUES
('This is SQL and Python and other fun teaching stuff'),
('More people should learn SQL from UMSI'),
('UMSI also teaches Python and also SQL');

CREATE TABLE docs_gin (
  keyword TEXT,
  doc_id INTEGER REFERENCES docs(id) ON DELETE CASCADE
);

<a href="05-FullText.sql" target="_blank">...</a>

pg4e=&gt; select * from docs_gin;
 keyword  | doc_id
----------+--------
 Python   |      1
 SQL      |      1
 This     |      1
 stuff    |      1
 teaching |      1
 More     |      2
 SQL      |      2
 UMSI     |      2
 from     |      2
 learn    |      2
 people   |      2
 should   |      2
 Python   |      3
 SQL      |      3
 UMSI     |      3
 also     |      3
 and      |      3
 teaches  |      3

</pre>
<b>References</b>
<ul>
    <li> <a href="https://stackoverflow.com/questions/29419993/split-column-into-multiple-rows-in-postgres"
            target="_blank">Split column into multiple rows in Postgres</a> (Stackoverflow)</li>
</ul>

<br clear="all"/>
<h2 id="inverted">Inverted Indexes in PostgreSQL
<?php doNav('inverted'); ?>
</h2>
<ul>
    <li>Generalized Inverse Index (GIN)</li>
    <li>Generalized Search Tree (GiST)</li>
</ul>
<p>
<i>GIN indexes are the preferred text search index type. </i>
Advantages: exact matches, efficient on lookup/  Disadvantages: can be costly when inserting or updating data
because every new word is inserted somewhere in the index and can get large.
Like the B-Tree, the GIN is the usual "go-to" inverted index and GiST is used in more special cases.
The previous example was a rough approximation of a GIN index.
<p>
Hashing is used to reduce the size of and cost to update the GiST.
<i>
A GiST index is lossy, meaning that the index might produce false matches, and it is necessary to check the actual table row to eliminate such false matches. (PostgreSQL does this automatically when needed.)
</i>
The indexes have equivalent functionality and will return the same rows - but
there may be performance / storage tradeoffs between GIN and GiST.
</p>
<p>
Both GIN and GiST want to know something about the type of array data it will be indexing
and the kinds of operations that we will be using in <b>WHERE</b> clauses.  In the example
below we are indexing arrays of strings (i.e. text[]) and will be using the "&lt;@" operator
(contained within).
</p>
<p>
We can build a simple GIN index like the manual index above:
<pre>
pg4e=&gt; CREATE TABLE docs (id SERIAL, doc TEXT, PRIMARY KEY(id));
CREATE TABLE

pg4e=&gt; CREATE INDEX gin1 ON docs USING gin(string_to_array(doc, ' ')  _text_ops);
CREATE INDEX

pg4e=&gt; INSERT INTO docs (doc) VALUES
pg4e-&gt; ('This is SQL and Python and other fun teaching stuff'),
pg4e-&gt; ('More people should learn SQL from UMSI'),
pg4e-&gt; ('UMSI also teaches Python and also SQL');
INSERT 0 3
pg4e=-- The &lt@ is looking for an intersection between two arrays
pg4e=&gt; SELECT id, doc FROM docs WHERE '{learn}' &lt;@ string_to_array(doc, ' ');
 id |                  doc
----+----------------------------------------
  2 | More people should learn SQL from UMSI

pg4e=&gt; EXPLAIN SELECT id, doc FROM docs WHERE '{learn}' &lt@ string_to_array(doc, ' ');
                                 QUERY PLAN
----------------------------------------------------------------------------
 Bitmap Heap Scan on docs  (cost=12.05..21.53 rows=6 width=32)
   Recheck Cond: ('{learn}'::text[] &lt@ string_to_array(doc, ' '::text))
   -&gt;  Bitmap Index Scan on gin1  (cost=0.00..12.05 rows=6 width=0)
         Index Cond: ('{learn}'::text[] &lt@ string_to_array(doc, ' '::text))
(4 rows)
</pre>


<b>References</b>
<ul>
    <li><a href="https://www.postgresql.org/docs/11/textsearch-indexes.html" target="_blank">
            GIN and Gist Indexes in PostgreSQL</a></li>
</ul>
<br clear="all"/>
<h2 id="stemming">Inverted Indexes of Natural Language - Stemming and Stop Words
<?php doNav('stemming'); ?>
</h2>
<div style="float: right; width: 25%; min-width: 250;"><a href="05-FullText-images/inverted-index-stop.png" target="_blank">
<img src="05-FullText-images/inverted-index-stop.png" style="border: 2px solid black; width: 100%;">
</a></div>
<p>
To take advantage of the "naruralness" of natural language, we need to ignore words that convey no meaning
and consistently reduce variations of words with equivalent meanings down to a single "stem word".
</p>
<p>
Recall this failure
<pre>
pg4e=&gt; SELECT DISTINCT id, doc FROM docs AS D
pg4e-&gt; JOIN docs_gin AS G ON D.id = G.doc_id
pg4e-&gt; WHERE G.keyword = ANY(string_to_array('Search for Lemons and Neons', ' '));
 id |                         doc
----+-----------------------------------------------------
  1 | This is SQL and Python and other fun teaching stuff
  3 | UMSI also teaches Python and also SQL
</pre>
The word "and" contributed no real meaning to our query.  And it took up valuable space in our GIN
index. So we put it on the
<a href="https://en.wikipedia.org/wiki/Stop_words" target="_blank">stop word</a> list.
Lets stop word and
<a href="https://en.wikipedia.org/wiki/Stemming" target="_blank">stemming</a> capabilities
by hand and then just use PostgreSQL features to build a natural language search.
<pre>
pg4e=&gt; SELECT * FROM stop_words;
 word
------
 is
 this
 and
(3 rows)

pg4e=&gt; SELECT * FROM docs_stem;
   word   | stem
----------+-------
 teaching | teach
 teaches  | teach
(2 rows)

<a href="05-FullText.sql" target="_blank">...</a>

pg4e=&gt; select * from docs_gin;
 keyword | doc_id
---------+--------
 also    |      3
 from    |      2
 fun     |      1
 learn   |      2
 more    |      2
 other   |      1
 people  |      2
 python  |      1
 python  |      3
 should  |      2
 sql     |      3
 sql     |      1
 sql     |      2
 stuff   |      1
 teach   |      3
 teach   |      1
 this    |      1
 umsi    |      2
 umsi    |      3
(19 rows)
</pre>

<p>
Stemming and stop words (and the meaning of "meaning") depend on which language is stored in the text column.
The default install of PostgreSQL knows the rules for
a few languages and more can be installed:
<pre>
pg4e=&gt; SELECT cfgname FROM pg_ts_config;
  cfgname
------------
 simple
 danish
 dutch
 english
 finnish
 french
 german
 hungarian
 italian
 norwegian
 portuguese
 romanian
 russian
 spanish
 swedish
 turkish
(16 rows)
</pre>

<h2 id="text-search">Text Search Functions
<?php doNav('text-search'); ?>
</h2>
<p>
PostgreSQL provides some functions that turn a text document/string into an "array" with stemming, stop words, and other
language-oriented features.
</p>
<p>
<b>ts_vector()</b> returns a list of words that represent the document.
<b>ts_query()</b> returns a list of words with operators to representaions various logical combinations of words
        much like
        <a href="https://www.google.com/advanced_search" target="_blank">Google's Advanced Search</a>.
<pre>
pg4e=&gt; SELECT to_tsvector('english',
pg4e(&gt;     'UMSI also teaches Python and also SQL');
                   to_tsvector
--------------------------------------------------
 'also':2,6 'python':4 'sql':7 'teach':3 'umsi':1
(1 row)

pg4e=&gt; SELECT to_tsquery('english', 'teaching');
 to_tsquery
------------
 'teach'
(1 row)
</pre>
</p>
<p>
In a <b>WHERE</b> clause we use the
<b>@@</b> operator to ask is a <b>ts_query</b> matches a <b>ts_vector</b>.
</p><p>
    <pre>
pg4e=&gt; SELECT to_tsquery('english', 'teaching') @@
pg4e-&gt;   to_tsvector('english', 'UMSI also teaches Python and also SQL');
 ?column?
----------
 t
</pre>
</p>
<b>References</b>
<ul>
    <li><a href="https://www.postgresql.org/docs/11/textsearch-intro.html" target="_blank">
            Introduction to Text Search</a> (PostgreSQL)</li>
    <li><a href="https://www.postgresql.org/docs/11/functions-textsearch.html" target="_blank">
            Operators and functions that work with ts_query and ts_vector</a> (PostgreSQL)</li>
</ul>
<h2 id="natural">Making a Natural Language Inverted Index with PostgreSQL
<?php doNav('natural'); ?>
</h2>
<p>
As you might expect, letting PostgreSQL do all the work is the easy part.
Stop words and stems are all handled in the "ts_" functions.  And the
GIN knows what operations you will be using automatically when you
pass in a <b>ts_vector</b>.
<pre>
pg4e=&gt; CREATE TABLE docs (id SERIAL, doc TEXT, PRIMARY KEY(id));
CREATE TABLE
pg4e=&gt; CREATE INDEX gin1 ON docs USING gin(to_tsvector('english', doc));
CREATE INDEX
pg4e=&gt;
pg4e=&gt; INSERT INTO docs (doc) VALUES
pg4e-&gt; ('This is SQL and Python and other fun teaching stuff'),
pg4e-&gt; ('More people should learn SQL from UMSI'),
pg4e-&gt; ('UMSI also teaches Python and also SQL');
INSERT 0 3
pg4e=&gt;
pg4e=&gt; SELECT id, doc FROM docs WHERE
pg4e-&gt;     to_tsquery('english', 'learn') @@ to_tsvector('english', doc);
 id |                  doc
----+----------------------------------------
  2 | More people should learn SQL from UMSI

pg4e=&gt; EXPLAIN SELECT id, doc FROM docs WHERE
pg4e-&gt;     to_tsquery('english', 'learn') @@ to_tsvector('english', doc);
                                      QUERY PLAN
--------------------------------------------------------------------------------------
 Bitmap Heap Scan on docs  (cost=12.05..23.02 rows=6 width=36)
   Recheck Cond: ('''learn'''::tsquery @@ to_tsvector('english'::regconfig, doc))
   -&gt;  Bitmap Index Scan on gin1  (cost=0.00..12.05 rows=6 width=0)
         Index Cond: ('''learn'''::tsquery @@ to_tsvector('english'::regconfig, doc))
</pre>

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
pg4e=&gt; SELECT id, subject, sender,
pg4e-&gt;   ts_rank(to_tsvector('english', body), to_tsquery('english', 'personal &amp; learning')) as ts_rank
pg4e-&gt; FROM messages
pg4e-&gt; WHERE to_tsquery('english', 'personal &amp; learning') @@ to_tsvector('english', body)
pg4e-&gt; ORDER BY ts_rank DESC;
 id |          subject           |           sender           | ts_rank
----+----------------------------+----------------------------+----------
  4 | re: lms/vle rants/comments | Michael.Feldstein@suny.edu | 0.282352
  5 | re: lms/vle rants/comments | john@caret.cam.ac.uk       |  0.09149
  7 | re: lms/vle rants/comments | john@caret.cam.ac.uk       |  0.09149

pg4e=&gt; SELECT id, subject, sender,
pg4e-&gt;   ts_rank_cd(to_tsvector('english', body), to_tsquery('english', 'personal &amp; learning')) as ts_rank
pg4e-&gt; FROM messages
pg4e-&gt; WHERE to_tsquery('english', 'personal &amp; learning') @@ to_tsvector('english', body)
pg4e-&gt; ORDER BY ts_rank DESC;
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

<p><b>References</b></p>

<ul>
<li><a href="https://www.postgresql.org/docs/10/textsearch-controls.html#TEXTSEARCH-RANKING" target="_blank">
        Controlling Text Search</a> (PostgreSQL)</li>
<li><a href="https://stackoverflow.com/questions/4014519/fulltext-query-with-scores-ranks-in-postgresql/4014625#4014625"
       target="_blank">FULLTEXT query with scores/ranks in PostgreSQL</a> (Stackoverflow)</li>
<li><a href="https://stackoverflow.com/questions/12933805/best-way-to-use-postgresql-full-text-search-ranking"
       target="_blank">Best way to use PostgreSQL full text search ranking</a> (Stackoverflow) </li>
</ul>

<h2 id="list-index">There is a lot of ways to index and search in PostgreSQL
<?php doNav('list-index'); ?>
</h2>
<p>
You can ask PostgreSQL the different index / <b>WHERE</b> clause operator combinations
it supports.  There are quite a few.
<pre>
pg4e=&gt; SELECT am.amname AS index_method, opc.opcname AS opclass_name
pg4e-&gt;     FROM pg_am am, pg_opclass opc
pg4e-&gt;     WHERE opc.opcmethod = am.oid
pg4e-&gt;     ORDER BY index_method, opclass_name;
 index_method |      opclass_name
--------------+------------------------
 brin         | abstime_minmax_ops
 brin         | bit_minmax_ops
 brin         | box_inclusion_ops
 ...
 brin         | bpchar_minmax_ops
 brin         | uuid_minmax_ops
 brin         | varbit_minmax_ops
 btree        | abstime_ops
 btree        | array_ops
 ...
 btree        | tsquery_ops
 btree        | tsvector_ops
 btree        | uuid_ops
 gin          | _abstime_ops
 gin          | _bit_ops
 gin          | _bool_ops
 gin          | _date_ops
 ...
 gin          | _text_ops
 gin          | _time_ops
 gin          | _timestamp_ops
 gin          | _timestamptz_ops
 ...
 gin          | jsonb_ops
 gin          | jsonb_path_ops
 gin          | tsvector_ops
 gist         | box_ops
 gist         | circle_ops
 ...
 gist         | tsvector_ops
 hash         | abstime_ops
 hash         | aclitem_ops
 hash         | array_ops
 ...
 hash         | text_pattern_ops
 hash         | time_ops
 hash         | timestamp_ops
 spgist       | quad_point_ops
 spgist       | range_ops
 spgist       | text_ops
(159 rows)
</pre>
