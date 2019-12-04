<!DOCTYPE html>
<html lang="en">
    <head>
<title>PG4E - PostgreSQL JSON - Lecture Notes</title>
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
<h1 id="lecture">PostgreSQL / JSON Lecture Notes</h1>
<p>

</p>
<h2 id="three">Structured Data Columns
<?php doNav('three'); ?>
</h2>
<p>
There are three structured data column types:
<ul>
<li>

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
