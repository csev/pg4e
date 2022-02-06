<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;
use \Tsugi\Grades\GradeUtil;

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
    'nebraska',
    'confidentiality',
    'golden',
    'rwiki',
    'zeckoski',
    'rajgopalan',
    'shinozaki',
);

if ( U::get($_POST,'check') ) {

    $pg_PDO = false;
    // $client = get_es_connection();
    $client = get_es_local();
    if ( ! $client ) {
        $_SESSION['error'] = 'Could not connect to autograder ES instance';
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    foreach($words as $word) {
        $params = [
            'index' => $es_user,
            'body'  => [
                'query' => [
                    'match' => [
                        'body' => $word
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
    	    // echo("<pre>\n");print_r($response);echo("</pre>\n");
            if ( ! isset($response['hits']['hits']) ) {
                $msg = 'Error looking for '.$word;
                if ( isset($response['error']['type'])) $msg .= ' | ' . $response['error']['type'];
                if ( isset($response['error']['reason'])) $msg .= ' | ' . $response['error']['reason'];
                $_SESSION['error'] = $msg;
                header( 'Location: '.addSession('index.php') ) ;
                return;
            }
    	    $hits = $response['hits']['hits'];
    	    if ( count($hits) < 1 ) {
    		    $_SESSION['error'] = 'Query / match did not find '.$word;
                header( 'Location: '.addSession('index.php') ) ;
                return;
            }
    		
        } catch(Exception $e) {
            echo("<pre>\n");var_dump($e);return;
            pg4e_debug_note($pg_PDO, $e->getMessage());
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
<h1>Elasticsearch Email Load</h1>
<p>
You should download these files:
<ul>
<li>
<a href="https://www.pg4e.com/code/elasticmail.py" target="_blank">https://www.pg4e.com/code/elasticmail.py</a>
</li>
<li>
<a href="https://www.pg4e.com/code/datecompat.py" target="_blank">https://www.pg4e.com/code/datecompat.py</a>
</li>
</ul>
</p>
<p>
You will need to install the Python Elasticsearch library (version 7.13.x) if you have not already done so.
<pre>
pip install 'elasticsearch<7.14.0'
</pre>
</p>
<p>
If installing elasticsearch locally run <b>pip</b> with the <b>-m</b> flag enabled:
<pre>
macOS: python3 -m pip install 'elasticsearch<7.14.0'
</pre>
<pre>
Windows: python -m pip install 'elasticsearch<7.14.0'
</pre>
</p>
<p>
<?php
$endform = false;
pg4e_user_es_form($LAUNCH, $endform);
?>
</p>
<p>
Please enter your Python code in the space below the assignment instructions.
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
You must clear out your index and load at least the first 100 messages from 
<a href="http://mbox.dr-chuck.net/sakai.devel/" target="_blank">http://mbox.dr-chuck.net/sakai.devel/</a>
into the index to complete this assignment.  You should not need to change any code to make this happen.
This is example code you can refer to in the future of pulling data from an API and pushing it into Elasticsearch.
</p>
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
