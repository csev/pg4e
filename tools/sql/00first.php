<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$MAX_UPLOAD_FILE_SIZE = 1024*1024;

require_once "sql_util.php";

// echo("<pre>\n");var_dump($_POST);die('zap');

if ( ! pg4e_user_db_load($LAUNCH) ) return;

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {
    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    $meta = pg4e_setup_meta($LAUNCH, $pg_PDO);
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

	$sql = "SELECT id, keystr, valstr, created_at, updated_at FROM pg4e_meta";
	if ( ! pg4e_query_return_error($pg_PDO, $sql) ) return;

	// $sql = "SELECT id, link_id, score, title, note, debug_log, created_at, updated_at FROM pg4e_result";
	// if ( ! pg4e_query_return_error($pg_PDO, $sql) ) return;

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
<h1>Our First Tables</h1>
<?php pg4e_user_db_form($LAUNCH); ?>
<p>Log in to your database and create the following tables.</p>
<p>The <b>pg4e_debug</b> table will let you see the queries that were run by the
auto grader as it is grading your assignment.  It is cleared out at the beginning
of each autograder attempt.
<pre>
CREATE TABLE pg4e_debug (
  id SERIAL,
  query VARCHAR(4096),
  result VARCHAR(4096),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(id)
);
</pre>
You can view the contents of this table after running the autograder with this command:
<pre>
SELECT query, result, created_at FROM pg4e_debug;
</pre>
</p>
<p>Creating the <b>pg4e_result</b> table is optional in the case you do not want to store any
of your Personally Identifiable Information (PII) on a third party server.
</p>
<p>If you create the <b>pg4e_result</b> table, the auto-graders
will store a copy of the grades you receive from each assignment
along with some error message detail if something goes wrong.
These scores are also sent to your learning system of record so changing or deleting your scores
in this table will not accomplish anything :).
<pre>
CREATE TABLE pg4e_result (
  id SERIAL,
  link_id INTEGER UNIQUE,
  score FLOAT,
  title VARCHAR(4096),
  note VARCHAR(4096),
  debug_log VARCHAR(8192),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP
);
</pre></p>
<p><b>Note:</b> You will see a <b>pg4e_meta</b> table in your database that is used
internally by the autograder to pass
information from one assignment to another assignment.  The autograder will
store mysterious stuff in this table and look at it later.  Leave this table alone.
If this table does not exist or you change the data the autograder puts in
this table, the autograder may refuse to grade your assignments.
</p>
