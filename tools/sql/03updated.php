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

    $sql = "DELETE from keyvalue;";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    $sql = "INSERT INTO keyvalue (key, value) VALUES ('number', 42);";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    $sql = "SELECT id, key, value, created_at, updated_at FROM keyvalue;";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    $row1 = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ( ! $row1 ) {
        $_SESSION['error'] = "Could not fetch inserted row";
        header('Location: '.addSession('index.php'));
        return;
    }

    $sql = "UPDATE keyvalue SET value=43 WHERE key='number';";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    $sql = "SELECT id, key, value, created_at, updated_at FROM keyvalue;";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    $row2 = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ( ! $row2 ) {
        $_SESSION['error'] = "Could not fetch updated row";
        header('Location: '.addSession('index.php'));
        return;
    }

	if ( $row1['created_at'] != $row2['created_at'] ) {
        $_SESSION['error'] = "The value for created_at was changed by an UPDATE. old=".$row1['created_at']." new=".$row2['created_at'];
        header('Location: '.addSession('index.php'));
        return;
    }

	if ( $row1['updated_at'] == $row2['updated_at'] ) {
        $_SESSION['error'] = "The value for updated_at was not changed by an UPDATE. old=".$row1['updated_at']." new=".$row2['updated_at'];
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
<h1>Making a Stored Procedure</h1>
<p>In this assignment you will create a table, and add a stored procedure to it.
<?php pg4e_user_db_form($LAUNCH); ?>
Create this table:
<pre>
CREATE TABLE keyvalue ( 
  id SERIAL,
  key VARCHAR(128) UNIQUE,
  value VARCHAR(128) UNIQUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  PRIMARY KEY(id)
);
</pre>
<p>
Add a stored procedure so that every time a record is updated, the
<b>updated_at</b> variable is automatically set to the current time.
This was covered in the lecture materials.
The auto grader will insert some records, then update them and check
to see if <b>updated_at</b> is updated appropriately.
