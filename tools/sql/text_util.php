<?php

require_once('stem_util.php');

function get_lines($code) {
    $fa = file('python-intro/01-intro.txt');
    $fpos = $code % (count($fa)-10);
    $lines = array();
    for($i = 0; $i< 10; $i++) {
        $lines[] = trim($fa[$fpos+$i]);
    }
    return $lines;
}

function get_all_words($lines) {
    $words = array();
    foreach($lines as $line) {
        $pieces = explode(' ', $line);
        foreach($pieces as $piece ) {
            $piece = strtolower(trim($piece));
            if ( strlen($piece) > 0 ) $words[] = $piece;
        }
    }
    return $words;
}

function get_gin($lines, $stop_words=false) {
    $gin = array();
    $i = 0;
    foreach($lines as $line) {
        $i++;
        $pieces = explode(' ', $line);
        foreach($pieces as $piece ) {
            $piece = strtolower(trim($piece));
            if ( is_array($stop_words) && in_array(strtolower($piece), $stop_words) ) continue;
            if ( !isset($gin[$piece]) ) {
                $gin[$piece] = array($i);
                continue;
            }
            $old = $gin[$piece];
            if ( in_array($i, $old) ) continue;
            $old[] = $i;
            $gin[$piece] = $old;
        }
    }
    return $gin;
}

function get_stems($gin, &$stemcount) {
    $stems = array();
    foreach($gin as $word => $lines) {
        $stem = PorterStemmer::Stem($word);
        if ( $stem != $word ) {
            if ( ! isset($stemcount[$word]) ) $stemcount[$word] = 0;
            $stemcount[$word]++;
        }
        if ( $stem != $word && ! isset($stems[$word]) ) {
            $stems[$word] = $stem;
        }
    }
    return $stems;
}

// https://www.ranks.nl/stopwords
function get_stop_words() {
    $stop_words = array( 'i', 'a', 'about', 'an', 'are', 'as',
        'at', 'be', 'by', 'com', 'for', 'from', 'how', 'in',
        'is', 'it', 'of', 'on', 'or', 'that', 'the', 'this', 'to',
        'was', 'what', 'when', 'where', 'who', 'will', 'with', 'the',
        'www');
    return $stop_words;
}

function insert_docs($table, $lines) {
?>
<pre>
INSERT INTO <?= $table ?> (doc) VALUES
<?php
$n = count($lines);
foreach($lines as $line) {
    $n--;
    echo("('".htmlentities($line)."')");
    if ( $n == 0 ) {
        echo(";\n");
    } else {
        echo(",\n");
    }
}

$max_rows = 10;
?>
</pre>
<?php
}
