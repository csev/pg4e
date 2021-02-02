<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

// https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/quickstart.html
require_once "names.php";
require_once "text_util.php";
require_once "sql_util.php";
require_once "es_util.php";

if ( ! pg4e_user_es_load($LAUNCH) ) return;

// Compute the stuff for the output
$code = getCode($LAUNCH);

$config = getCourseSettings();

// http://www.gutenberg.org/cache/epub/22381/pg22381.txt
$books = array(
 '14091' => 'repose',
 '18866' => 'eccentric',
 '19337' => 'charitable',
 '22381' => 'prometheus',
 '20203' => 'pennsylvania',
);

$config = getCourseSettings();

$book_ids = array_keys($books);
$MT = new Mersenne_Twister($code);
// TODO: Add -1
$pos = $MT->getNext(0,count($book_ids));
if ( $pos >= count($book_ids) ) $pos = 0;

$book_id = $book_ids[$pos];
$book_url = 'http://www.gutenberg.org/cache/epub/'.$book_id.'/pg'.$book_id.'.txt';
$book_url = 'https://www.pg4e.com/gutenberg/cache/epub/'.$book_id.'/pg'.$book_id.'.txt';
$word = $books[$book_id];

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {

    $pg_PDO = false;
    // $client = get_es_connection();
    $client = get_es_local();
    if ( ! $client ) return;

    $params = [
        'index' => $es_user,
        'body'  => [
            'query' => [
                'match' => [
                    'content' => $word
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
        if ( ! isset($response['hits']['hits']) ) {
            $msg = 'Error looking for '.$word;
            if ( isset($response['error']['type'])) $msg .= ' | ' . $response['error']['type'];
            if ( isset($response['error']['reason'])) $msg .= ' | ' . $response['error']['reason'];
            $_SESSION['error'] = $msg;
            header( 'Location: '.addSession('index.php') ) ;
            return;
        }
        $hits = count($response['hits']['hits']);
        if ( $hits < 1 ) {
            $_SESSION['error'] = 'Query / match did not find '.$word;
            header( 'Location: '.addSession('index.php') ) ;
            return;
        }
            
    } catch(Exception $e) {
        pg4e_debug_note($pg_PDO, $e->getMessage());
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
<h1>Elasticsearch Book Load</h1>
<p>
In this assignment you will download a book from:
<pre>
<a href="<?= $book_url ?>" target="_blank"><?= $book_url ?></a>
</pre>
and
create an Elasticsearch index called <b><?= $es_user ?></b>
in the following Elasticsearch instance:
<?php pg4e_user_es_form($LAUNCH); ?>
</p>
<p>
Your code should clear out the index at the beginning so you start with a fresh index each
time the code is executed.
<p>
You should download these files:
<ul>
<li>
<a href="https://www.pg4e.com/code/elasticbook.py" target="_blank">https://www.pg4e.com/code/elasticbook.py</a>
</li>
<li>(If you have not already done this)
<a href="https://www.pg4e.com/code/hidden-dist.py" target="_blank">https://www.pg4e.com/code/hidden-dist.py</a>
</li>
</ul>
If necessary, copy <b>hidden-dist.py</b> to <b>hidden.py</b> and put your Elasticsearch host/prefix/port/account/password values
into the <b>elastic()</b> method.  You should also put your PostgreSQL secrets into this file as well.
</p>
<p>
You will need to install the Python Elasticsearch library if you have not already done so.
<pre>
pip install elasticsearch
</pre>
</p>
<p>
This autograder will run a command equivalent to using the <b>elastictool.py</b> command as follows:
<pre>
$ python3 elastictool.py

Enter command: search <?= htmlentities($word) ?> 
https://pg4e_dca2724d69:*****@www.pg4e.com:443/elasticsearch/pg4e_dca2724d69/_search?pretty
{"query": {"query_string": {"query": "<?= htmlentities($word) ?>"}}}
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
      "value": 5,
      "relation": "eq"
    },
    "max_score": 5.850847,
    "hits": [
      {
        "_index": "pg4e_dca2724d69",
        "_type": "_doc",
        "_id": "08083e37e9ee35b2e67385ba6dee0de1f704945063c65a22b517185f5df1cf0b",
        "_score": 5.850847,
        "_source": {
          "offset": 1237,
          "content": " is a distinct <?= htmlentities($word) ?> from the ..."
        }
      }
    ]
  }
}


</pre>
And expect to get at least one hit.
</p>
<p>
You can download <b>elastictool.py</b> at 
<a href="https://www.pg4e.com/code/elastictool.py" target="_blank">https://www.pg4e.com/code/elastictool.py</a>.
You will need to setup the <b>hidden.py</b> with your Elasticsearch host/port/account/password values.
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

