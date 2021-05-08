<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$MAX_UPLOAD_FILE_SIZE = 1024*1024;

require_once "sql_util.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

$code = getCode($LAUNCH);
$MT = new \Tsugi\Util\Mersenne_Twister($code);


$autos = pg4e_load_csv("makemodel.csv");
sort($autos);
$first = $MT->getNext(0,count($autos)-5);
$second = $MT->getNext(0,count($autos)-5);
if ( $first > $second ) { $tmp = $first; $first = $second; $second = $tmp; }  // Order
if ( ($first+3) > $second ) $second = $second + 3; // Overlap
$answer = array_merge(array_slice($autos,$first,3), array_slice($autos,$second,2));

$sql = "SELECT make.name, model.name
    FROM model
    JOIN make ON model.make_id = make.id
    ORDER BY make.name LIMIT 5;";

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {

    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    $good = 0;
    $pos = 0;
    $error = false;
    $found = false;
    while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
        $ans = $answer[$pos];
        foreach($ans as $i => $txt ) {
            if ($row[$i] != $txt ) {
                $pos++; $col = $i + 1;
                $_SESSION['error'] = "Row $pos column $col expected $txt, got ".$row[$i];
                $error = true;
                break;
            }
        }
        if ( $error ) break;
        $good++;
        $pos++;
    }
    $stmt->closeCursor();

    if ( $pos == 0 ) {
        $_SESSION['error'] = "No records found in the make and model tables";
        $error = true;
    }

    if ( $good < 3 ) {
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    $gradetosend = 1.0;
    pg4e_grade_send($LAUNCH, $pg_PDO, $oldgrade, $gradetosend, $dueDate);

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

if ( $RESULT->grade > 0 ) {
    echo('<p class="alert alert-info">Your current grade on this assignment is: '.($RESULT->grade*100.0).'%</p>'."\n");
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<h1>Entering Many-to-One Data - Automobiles</h1>
<p>
This application will create two tables, and hand-load a
small amount of data in the the tables while properly normalizing the data.
Once you have placed the proper data in the tables, press the button below to
check your answer.
</p>
<?php pg4e_user_db_form($LAUNCH); ?>
<p>
Here is the structure of the tables you will need for this assignment:
<pre>
CREATE TABLE make (
    id SERIAL,
    name VARCHAR(128) UNIQUE,
    PRIMARY KEY(id)
);

CREATE TABLE model (
  id SERIAL,
  name VARCHAR(128),
  make_id INTEGER REFERENCES make(id) ON DELETE CASCADE,
  PRIMARY KEY(id)
);
</pre>
Insert the following data into your database separating it
appropriately into the <b>make</b> and <b>model</b> tables and
setting the <b>make_id</b> foreign key to link each model to
its corresponding make.
<table border="2">
<tr>
<th>make</th><th>model</th>
</tr>
<?php
$pos=0;
foreach($answer as $ans) {
    echo("<tr>");
    foreach($ans as $i => $txt ) {
        echo("<td>".htmlentities($txt)."</td>");
    }
    echo("<tr>\n");
    $pos++;
    if ( $pos >= 5 ) break;
}
?>
</table>
</p>
<p>
To grade this assignment, the program will run a query like this on
your database and look for the data above:
<pre>
<?= htmlentities($sql) ?>
</pre>
<?php
if ( ! $LAUNCH->user->instructor ) return;
?>
<p>
Instructor-only cheat sheet:
<pre>
<?php
foreach($answer as $ans) {
	echo("INSERT INTO make (name) VALUES ('".$ans[0]."') ON CONFLICT (name) DO NOTHING;\n");
	echo("INSERT INTO model (name, make_id) VALUES ('".$ans[1]."', (SELECT id FROM make WHERE name='".$ans[0]."'));\n");
}
?>
</pre>
