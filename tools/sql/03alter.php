<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

// Compute the stuff for the output
$code = getCode($LAUNCH);
$column = 'neon' . ($code % 1000);
$sql = "SELECT $column FROM pg4e_debug LIMIT 1;";

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {
    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) {
        $_SESSION['error'] = "SQL Failed: ".$sql;
        header('Location: '.addSession('index.php'));
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
<h1>Using ALTER TABLE</h1>
<p>
In this exercise you will add a column to your <b>pg4e_debug</b> table.
The column can be any type you like - like INTEGER.
<b><?= $column ?></b>.
</p>
<p>The auto grader will run the folowing command:
<pre>
<?= $sql ?>
</pre>
The auto grader does not need to get any data - the above SQL simply needs
to not fail due to a syntax error (i.e. the column must exist).
<?php pg4e_user_db_form($LAUNCH); ?>
