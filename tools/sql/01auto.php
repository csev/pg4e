<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

// Compute the stuff for the output
$code = getCode($LAUNCH);

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {
    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

    $sql = "DELETE FROM automagic;";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    $sql = "INSERT INTO automagic (name, height) VALUES ('Arthur Dent', 160);";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    $sql = "SELECT id, name, height FROM automagic;";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

	// array(2) { [0]=> int(1) [1]=> string(11) "Arthur Dent" } 
	$row = $stmt->fetch(\PDO::FETCH_NUM);

	if ( ! is_int($row[0]) || $row[0] < 1 ) {
        $_SESSION['error'] = "Expecting id column to be an integer, found ".$row[0];
        header('Location: '.addSession('index.php'));
        return;
    }

	// Check to see if Height is required
	pg4e_debug_note($pg_PDO, '**** This next query is supposed to fail ****');
    $sql = "INSERT INTO automagic (name) VALUES ('Ford Prefect');";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);

    if ( $stmt ) {
        $_SESSION['error'] = "Expecting an INSERT without a height to fail, it did not fail";
        header('Location: '.addSession('index.php'));
        return;
    }
	unset($_SESSION['error']);  // Don't want the error to show up.

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
<h1>SERIAL fields / Auto increment</h1>
<p>In this assignment you will create a table, and the autograder will insert a few rows to test your schema.</p>
<p>
Create a table named <b>automagic</b> with the following fields:
<ul>
<li>An <b>id</b> field that is an auto incrementing serial field.
</li>
<li>A <b>name</b> field that allows up to 32 characters but no more  This field is required.
</li>
<li>A <b>height</b> field that is a floating point number that is required.
<a href="https://www.postgresql.org/docs/current/ddl-constraints.html" target="_blank">PostgreSQL Constraints</a>.
</li>
</ul>
When the table is created, check your answer.
</p>
<?php pg4e_user_db_form($LAUNCH); ?>
