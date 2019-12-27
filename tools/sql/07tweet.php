<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

// https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/quickstart.html
require_once "names.php";
require_once "text_util.php";
require_once "es_util.php";

if ( ! pg4e_user_es_load($LAUNCH) ) return;

// Compute the stuff for the output
$code = getCode($LAUNCH);

$lines = get_lines($code, 5);
$gin = get_gin($lines);
ksort($gin);

// Find the longest word
$word = '';
foreach($gin as $keyword => $docs) {
    if(strlen($keyword) > strlen($word)) $word = $keyword;
}

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {
	$es = get_es_connection();
    if ( ! $es ) return;

/*
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    // Get one row
    $failure = false;
    $row = $stmt->fetch(\PDO::FETCH_NUM);
    if ( $row == false ) {
        $_SESSION['error'] = "Unable to retrieve a row with the keyword '$word'";
        $failure = true;
    }

    $found = array();
    $stmt = pg4e_query_return_error($pg_PDO, "EXPLAIN ".$sql);
    if ( ! $stmt ) return;

    echo("<pre>\n");
    while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
        $line = $row[0];
        pg4e_debug_note($pg_PDO, "Explain retrieved: ".$line);

        if ( strpos($line, "Seq Scan") !== false ) {
            $_SESSION['error'] = "EXPLAIN query found 'Seq Scan', check pg4e_debug for details.";
            $failure = true;
        }
    }

    if ( $failure ) {
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    $gradetosend = 1.0;
    pg4e_grade_send($LAUNCH, $pg_PDO, $oldgrade, $gradetosend, $dueDate);

*/
    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<h1>ELastic Search Tweeets</h1>
<p>
In this search you will create an elastic search index called <b>tweet-index</b>
in the following Elastic Search instance:
<?php pg4e_user_es_form($LAUNCH); ?>
</p>
Use the following mapping for your index:
<pre>
{
    "mappings": {
        "tweet": {
            "properties": {
                "author": {
                    "type": "keyword"
                },
                "text": {
                    "type": "text"
                },
                "timestamp": {
                    "type": "date"
                },
            }
        }
    }
}
</pre>
Insert the following tweets (The author and date can be anything):
<p>
<pre>
<?php 
foreach($lines as $line) { 
  echo($line."\n");
}
?>
</pre>
</p>
<p>
You can start with this code
<a href="https://www.pg4e.com/code/elastic0.py" target="_blank">https://www.pg4e.com/code/elastic0.py</a>
</p>
