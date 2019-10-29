<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$MAX_UPLOAD_FILE_SIZE = 1024*1024;

require_once "sql_util.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {

    $pg_PDO = pg4e_get_user_connection($pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($pg_PDO) ) return;

    $gradetosend = 1.0;
    $scorestr = "Your answer is correct, score saved.";
    if ( $dueDate->penalty > 0 ) {
        $gradetosend = $gradetosend * (1.0 - $dueDate->penalty);
        $scorestr = "Effective Score = $gradetosend after ".$dueDate->penalty*100.0." percent late penalty";
    }
    if ( $oldgrade > $gradetosend ) {
        $scorestr = "New score of $gradetosend is < than previous grade of $oldgrade, previous grade kept";
        $gradetosend = $oldgrade;
    }

    // Use LTIX to send the grade back to the LMS.
    $debug_log = array();
    $retval = LTIX::gradeSend($gradetosend, false, $debug_log);
    $_SESSION['debug_log'] = $debug_log;

    if ( $retval === true ) {
        $_SESSION['success'] = $scorestr;
    } else if ( is_string($retval) ) {
        $_SESSION['error'] = "Grade not sent: ".$retval;
    } else {
        echo("<pre>\n");
        var_dump($retval);
        echo("</pre>\n");
        die();
    }

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<h1>Our First Tables</h1>
<p>
At this point you should have your <b><?= $pdo_database ?></b>database created
and have given access to the specified role.  You will now create a few tables
that the autograder will use to communicate with you.
</p>
<?php pg4e_user_db_form($LAUNCH); ?>
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
<p>The <b>pg4e_meta</b> table is used internally by the autograder to pass
information from one assignment to another assignment.  The autograder will
store mysterious stuff in this table and look at it later.  Leave this table alone.
<pre>
CREATE TABLE pg4e_meta (
  id SERIAL,
  keystr VARCHAR(128),
  valstr VARCHAR(4096),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP,
  PRIMARY KEY(id)
);
</pre></p>
<p>The <b>pg4e_results</b> table will store a copy of the grades you receive from each assignment.
These scores are also sent tou your learning system of record so changing or deleting your scores
in this table will not accomplish anything :).
<pre>
CREATE TABLE pg4e_results (
  id SERIAL,
  link_id INTEGER,
  score FLOAT,
  title VARCHAR(4096),
  notes VARCHAR(4096),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP
}
</pre></p>
