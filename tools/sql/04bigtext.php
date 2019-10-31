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

    $sql = "SELECT content FROM bigtext LIMIT 1;";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

	for($i=0;$i<5;$i++) {
       	$record = rand(100000, 200000);
       	$sql = "SELECT content FROM bigtext WHERE content LIKE '%".$record."%';";
    	$stmt = pg4e_query_return_error($pg_PDO, $sql);
    	if ( ! $stmt ) return;
    	$row1 = $stmt->fetch(\PDO::FETCH_ASSOC);
    	if ( ! $row1 ) {
        	$_SESSION['error'] = "Could not fetch inserted row ".$record;
        	header('Location: '.addSession('index.php'));
        	return;
    	}
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
<h1>Generating Text</h1>
<p>In this assignment you will create a table named
<b>bigtext</b> with a single <b>TEXT</b> column named
<b>content</b>.  Insert 100000 records into the table
that look as follows:
<pre>
This is record number 100000 of quite a few text records.
This is record number 100001 of quite a few text records.
...
This is record number 199999 of quite a few text records.
This is record number 200000 of quite a few text records.
</pre>
<?php pg4e_user_db_form($LAUNCH); ?>
