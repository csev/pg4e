<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;
use \Tsugi\Grades\GradeUtil;

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

    $pg_PDO = false;
    // $client = get_es_connection();
    $client = get_es_local();
    if ( ! $client ) {
        $_SESSION['error'] = 'Could not connect to autograder ES instance';
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    $params = [
        'index' => $es_user,
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
    	// echo("<pre>\n");print_r($response);echo("</pre>\n");die();
        if ( ! isset($response['hits']['hits']) ) {
            $msg = 'Error looking for '.$word;
            if ( isset($response['error']['type'])) $msg .= ' | ' . $response['error']['type'];
            if ( isset($response['error']['reason'])) $msg .= ' | ' . $response['error']['reason'];
    		$_SESSION['error'] = $msg;
            header( 'Location: '.addSession('index.php') ) ;
            return;
        }
        // var_dump($response['hits']['hits']); die();
    	$hits = count($response['hits']['hits']);
    	if ( $hits < 1 ) {
    		$_SESSION['error'] = 'Query / match did not find '.$word;
            header( 'Location: '.addSession('index.php') ) ;
            return;
        }
    		
    } catch(Exception $e) {
    	$_SESSION['error'] = 'Error: '.$e->getMessage();
        pg4e_debug_note($pg_PDO, $e->getMessage());
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
<h1>Elasticsearch Tweets</h1>
<p>
In this assignment you will create an Elasticsearch index
in the following Elasticsearch instance:
<?php
$endform = false;
pg4e_user_es_form($LAUNCH, $endform);
?>
</p>
<p>
Please enter your Python code in the space below the assignment instructions.
</p>
The index name should be the same as your user name and you should drop the index
before you insert
the following "tweets" (The author and date can be anything):
<p>
<pre>
<?php 
foreach($lines as $line) { 
  echo($line."\n");
}
?>
</pre>
Yes, the above lines do not look like tweets :)  They are randomly
generated and as such pretty bad tweets but they are the text you
are to use for this assignment.
</p>
<p>
You should download these files:
<ul>
<li>
<a href="https://www.pg4e.com/code/elastictweet.py" target="_blank">https://www.pg4e.com/code/elastictweet.py</a>
</li>
<li>
<a href="https://www.pg4e.com/code/hidden-dist.py" target="_blank">https://www.pg4e.com/code/hidden-dist.py</a>
</li>
</ul>
Then copy <b>hidden-dist.py</b> to <b>hidden.py</b> and put your Elasticsearch host/prefix/port/account/password values
into the <b>elastic()</b> method.  You should also put your PostgreSQL secrets into this file as well.
</p>
<p>
You will need to install the Python Elasticsearch library:
<pre>
pip install elasticsearch    # or pip3
</pre>
</p>
<p>
This autograder will run a command equivalent to using the <b>elastictool.py</b> command as follows:
<pre>
$ python3 elastictool.py

Enter command: search bonsai
https://pg4e_dca2724d69:*****@www.pg4e.com:443/elasticsearch/pg4e_dca2724d69/_search?pretty
{"query": {"query_string": {"query": "bonsai"}}}
200
{
  "took": 1,
  "timed_out": false,
  "_shards": {
    "total": 1,
    "successful": 1,
    "skipped": 0,
    "failed": 0
  },
  "hits": {
    "total": {
      "value": 1,
      "relation": "eq"
    },
    "max_score": 0.2876821,
    "hits": [
      {
        "_index": "pg4e_dca2724d69",
        "_type": "_doc",
        "_id": "abc",
        "_score": 0.2876821,
        "_source": {
          "author": "kimchy",
          "type": "tweet",
          "text": "Elasticsearch: cool. bonsai cool.",
          "timestamp": "2021-02-02T17:18:08.166157"
        }
      }
    ]
  }
}

</pre>
And expect to get at least one hit.
</p>
<p>
You can download the code for <b>elastictool.py</b> at 
<a href="https://www.pg4e.com/code/elastictool.py" target="_blank">https://www.pg4e.com/code/elastictool.py</a>.
This depends on having the account values in <b>hidden.py</b>.
</p>
<!--
<?php
  if ( isset($_SESSION['last_parms']) ) {
    $jsonstr = json_encode($_SESSION['last_parms'], JSON_PRETTY_PRINT);
    unset($_SESSION['last_parms']);
    echo("Last Elasticsearch query:\n\n");
    echo(htmlentities($jsonstr, ENT_NOQUOTES));
    echo("\n\n");
  }
  if ( isset($_SESSION['last_response']) ) {
    $jsonstr = json_encode($_SESSION['last_response'], JSON_PRETTY_PRINT);
    unset($_SESSION['last_response']);
    echo("Last Elasticsearch response:\n\n");
    echo(htmlentities($jsonstr, ENT_NOQUOTES));
    echo("\n");
 }

?>
-->
<?php
if ( $LAUNCH->user->instructor ) {
  echo("<p>Note to instructors: Students can view source to see the last Elasticsearch request and response</p>");
}
?>
<p>
Please enter your Python code here:
<textarea id="code" name="code" style="width:100%; height: 100%; font-family:Courier,fixed;font-size:16px;color:blue;">
<?php
if ( U::get($_SESSION, 'lastcode')){
    echo(htmlentities(U::get($_SESSION, 'lastcode')));
}
?>
</textarea>
</form>
</p>

