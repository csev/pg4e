<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

// Compute the stuff for the output
$code = getCode($LAUNCH);
$MT = new Mersenne_Twister($code);
$my_names = array();
$my_age = array();
$howmany = $MT->getNext(4,6);
for($i=0; $i < $howmany; $i ++ ) {
    $name = $names[$MT->getNext(0,count($names)-1)];
    $age = $MT->getNext(13,40);
    $database[] = array($name, $age);
}
sort($database);

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {
    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

    $sql = "SELECT * FROM ages;";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    $rows = $stmt->fetchAll(\PDO::FETCH_NUM);
    $stmt->closeCursor();
    if ( count($rows) != count($database) ) {
        $_SESSION['error'] = "Expecting ".count($database)." rows, retrieved ".count($rows);
        header('Location: '.addSession('index.php'));
        return;
    }

	for($pos=0; $pos< count($rows); $pos++) {
        $good = $database[$pos];
        $row = $rows[$pos];
        if ( $good[0] == $row[0] && $good[1] == $row[1] ) continue;
        $_SESSION['error'] = "Error on row ". ($pos+1) .
           " expected (".htmlentities($good[0]).', '.htmlentities($good[1]).')'.
           " found (".htmlentities($row[0]).', '.htmlentities($row[1]).')';
         header('Location: '.addSession('index.php'));
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
if ( isset($_SESSION['debug']) ) {
    echo("<pre>\n");
    echo("Code=$code\n");
    echo("Howmany=$howmany\n");
    var_dump($sorted);
    echo("</pre>\n");
    unset($_SESSION['debug']);
}
?>
<h1>Instructions</h1>
<p>In this assignment you will create a table and insert a few rows.
<?php pg4e_user_db_form($LAUNCH); ?>
Create this table:
<pre>
CREATE TABLE ages ( 
  name VARCHAR(128), 
  age INTEGER
);
</pre>
<p>
Make sure the table is empty by deleting any rows that 
you previously inserted, then insert these rows (and only these rows) 
with the following commands:
<pre>
<?php
echo("DELETE FROM ages;\n");
foreach($database as $row) {
   echo("INSERT INTO ages (name, age) VALUES ('".$row[0]."', ".$row[1].");\n");
}
?>
</pre>
Once the inserts are done, check your answer.
