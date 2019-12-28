<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

// https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/quickstart.html
require_once "names.php";
require_once "text_util.php";
require_once "es_util.php";

if ( ! pg4e_user_es_load($LAUNCH) ) return;
if ( ! pg4e_user_db_load($LAUNCH) ) return;

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

	// This may or may not work
    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
	unset($_SESSION['error']);  // Ignore missing database connection
    if ( $pg_PDO && ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

	$client = get_es_connection();
    if ( ! $client ) return;

	$params = [
    	'index' => 'tweet-index',
    	'body'  => [
        	'query' => [
            	'match' => [
                	'text' => $word
            	]
        	]
    	]
	];
	$_SESSION['last_parms'] = $params;
	pg4e_debug_note($pg_PDO, json_encode($params, JSON_PRETTY_PRINT));


	try {
		unset($_SESSION['last_response']);
		$response = $client->search($params);
		$_SESSION['last_response'] = $response;
		pg4e_debug_note($pg_PDO, json_encode($response, JSON_PRETTY_PRINT));
		// echo("<pre>\n");print_r($response);echo("</pre>\n");
		$hits = $response['hits']['total'];
		if ( $hits < 1 ) {
			$_SESSION['error'] = 'Query / match did not find '.$word;
        	header( 'Location: '.addSession('index.php') ) ;
        	return;
    	}
			
	} catch(Exception $e) {
		$_SESSION['error'] = 'Error: '.$e->getMessage();
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    $gradetosend = 1.0;
    pg4e_grade_send($LAUNCH, $pg_PDO, $oldgrade, $gradetosend, $dueDate);

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<h1>Elastic Search Tweeets</h1>
<p>
In this assignment you will create an elastic search index called <b>tweet-index</b>
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
You can build your application by starting with this code -
<a href="https://www.pg4e.com/code/elastic0.py" target="_blank">https://www.pg4e.com/code/elastic0.py</a>.
You will need to setup the <b>hidden.py</b> with your elastic search host/port/account/password values.
</p>
<p>
This autograder will run a command equivalent to using the <b>elastictool.py</b> command as follows:
<pre>
$ python3 elastictool.py 

Index / document count
----------------------
searchguard / 0
tweet-index / 5

Enter command: <b>search tweet-index information</b>

{
  ...
  "hits" : {
    "total" : 1,
    "max_score" : 1.0024122,
    "hits" : [
      {
         ...
         "_index" : "tweet-index",
          "author" : "kimchy",
          "text" : "information that we will encounter in our lives When you first start",
          "timestamp" : "2019-12-27T10:09:23.586741"
        }
      }
    ]
  }
</pre>
And expect to get at least one hit.
</p>
<p>
You can download <b>elastictool.py</b> at 
<a href="https://www.pg4e.com/code/elastictool.py" target="_blank">https://www.pg4e.com/code/elastictool.py</a>.
You will need to setup the <b>hidden.py</b> with your elastic search host/port/account/password values.
</p>
<!--
<?php
  if ( isset($_SESSION['last_parms']) ) {
	$jsonstr = json_encode($_SESSION['last_parms'], JSON_PRETTY_PRINT);
	unset($_SESSION['last_parms']);
	echo("Last elastic search query:\n\n");
	echo(htmlentities($jsonstr, ENT_NOQUOTES));
	echo("\n\n");
  }
  if ( isset($_SESSION['last_response']) ) {
	$jsonstr = json_encode($_SESSION['last_response'], JSON_PRETTY_PRINT);
	unset($_SESSION['last_response']);
	echo("Last elastic search response:\n\n");
	echo(htmlentities($jsonstr, ENT_NOQUOTES));
	echo("\n");
 }

?>
-->
<?php
if ( $LAUNCH->user->instructor ) {
  echo("<p>Note to instructors: Students can view source to see the last elastic search request and response</p>");
}
?>

