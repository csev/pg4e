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
"json"
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
It turned out to be easier to add libraries to the back end languages like Python, PHP, and Java
to convert their data structures to JavaScript-formatted thatn to use XML to serialize data.
</p>
<p>
Douglas Crockford write a simple specification for "JSON", and put it up at 
<a href="https://www.json.org" target="_new">www.json.org</a> and programmers
started to use it in thier software development.
</p>
<p>
In order to make parsing and generating JSON simpler, JSON required all of the keys of 
key value pairs be surrounded by double quotes.
</p>
<p>
For those familiar with Python, JSON looks almost exactly like nested Python list and dictionary
constants.  And while Python was not so polular in 2001, at this point with Python and JavaScript emerging
as the most widely used languages, it makes reading JSON pretty natural for those skilled
in either language.
</p>
<p>
JSON has quickly become the dominant way to store and transfer data structures between programs. 
JSON is sent across networks, stored on files, and stored in databases.  As JavaScript became an emerging server
language and JSON specific databases like
<a href="https://www.mongodb.com/" target="_blank">MongoDB</a> were developed,
JSON has been used in more and more places.   
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
