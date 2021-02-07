<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";
require_once "text_util.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

// Compute the stuff for the output
$code = getCode($LAUNCH);

$number = floor(($code * 22) / 7) % 1000000;
$count = 300;

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {
    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

    $sql = "SELECT iter,val FROM pythonseq LIMIT $count;";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    // Lets find those pseudo-random numbers
    $failure = false;
    for($i=0; $i<$count; $i++) {
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( $row == false ) {
            $_SESSION['error'] = "Ran out of rows at ".($i+1);
            header( 'Location: '.addSession('index.php') ) ;
            $stmt->closeCursor();
            return;
        }
        $check = $row['val'];
        if ( $check != $number ) {
            $_SESSION['error'] = "Mis-match at row ".($i+1)." expected ".$number." found ".$check;
            header( 'Location: '.addSession('index.php') ) ;
            $stmt->closeCursor();
            return;
        }
        $number = floor(($number * 22) / 7) % 1000000;
    }
    $stmt->closeCursor();

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
<h1>Inserting a sequence of numbers in Python</h1>
<p>In this assignment, you will write a Python program to insert a sequence of
<?= $count ?> pseudorandom numbers into a database table named
<b>pythonseq</b> with the following schema:
<pre>
CREATE TABLE pythonseq (iter INTEGER, val INTEGER);
</pre>
You should drop and recreate the <b>pythonseq</b> table each time your application runs.
<?php pg4e_user_db_form($LAUNCH); ?>
<p>
The following Python code will generate your series of pseudo-random numbers:
<pre>
number = <?= $number ?>

for i in range(<?= $count ?>) :
    print(i+1, number)
    value = int((value * 22) / 7) % 1000000
</pre>
Here are some of the numbers in the sequence:
<pre>
<?php
for($i=0;$i<$count;$i++) {
    if ( $i < 10 || ($i % 100) == 99 ) echo(($i+1)." ".$number."\n");
    $number = floor(($number * 22) / 7) % 1000000;
}
?>
</pre>
