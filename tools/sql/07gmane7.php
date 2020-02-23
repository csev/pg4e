<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

// https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/quickstart.html
require_once "names.php";
require_once "text_util.php";
require_once "es_util.php";
require_once "sql_util.php";

if ( ! pg4e_user_es_load($LAUNCH) ) return;

// Compute the stuff for the output
$code = getCode($LAUNCH);

$config = getCourseSettings();

$words = array(
    'canberra',
    'transaction',
    'golden',
    'rwiki',
    'speelmon',
    'translation',
    'tatsuki',
);

if ( U::get($_POST,'check') ) {

	$client = get_es_connection();
    if ( ! $client ) return;

    foreach($words as $word) {
	    $params = [
            'index' => $es_prefix .'/' . $es_user,
    	    'body'  => [
        	    'query' => [
            	    'match' => [
                	    'body' => $word
            	    ]
        	    ]
    	    ]
	    ];
	    $_SESSION['last_parms'] = $params;

	    try {
		    unset($_SESSION['last_response']);
		    $response = $client->search($params);
		    $_SESSION['last_response'] = $response;
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
    }

    $gradetosend = 1.0;
    $pg_PDO = false;
    pg4e_grade_send($LAUNCH, $pg_PDO, $oldgrade, $gradetosend, $dueDate);

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<h1>Elastic Search 7.x Email Load</h1>
<p>Do this assignment using
<a href="index.php?es_version=elastic6">Elastic Search 6</a>
</p>
<p>
You should have a separate <b>elastic7</b> folder and download these files into it:
<ul>
<li>
<a href="https://www.pg4e.com/code/elastic7/elasticmail.py" target="_blank">https://www.pg4e.com/code/elastic7/elasticmail.py</a>
</li>
<li>
<a href="https://www.pg4e.com/code/elastic7/datecompat.py" target="_blank">https://www.pg4e.com/code/elastic7/datecompat.py</a>
</li>
</ul>
</p>
<p>
You will need to install the Python ElasticSearch library if you have not already done so.
<pre>
pip install elasticsearch
</pre>
</p>
<p>
<?php pg4e_user_es_form($LAUNCH); ?>
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
<?php if ( U::get($config, 'proxy') == 'yes' ) { ?>
<p>
If you are behind a firewall, you may need to change urls in your Python code like:
<pre>
http://mbox.dr-chuck.net/sakai.devel/100/101
</pre>
to
<pre>
<?= $CFG->apphome ?>/proxy/http://mbox.dr-chuck.net/sakai.devel/100/101
</pre>
To retrieve the data through a firewall.
</p>
<?php } ?>

<?php
if ( $LAUNCH->user->instructor ) {
  echo("<p>Note to instructors: Students can view source to see the last elastic search request and response</p>");
}
?>

