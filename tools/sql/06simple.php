<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";
require_once "text_util.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

// Compute the stuff for the output
$code = getCode($LAUNCH);

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {
    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

    $sql = "SELECT line FROM pythonfun WHERE line LIKE 'Have a nice%';";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    // Get one row
    $failure = false;
    $row = $stmt->fetch(\PDO::FETCH_NUM);
    $stmt->closeCursor();
    if ( $row == false ) {
        $_SESSION['error'] = "Unable to retrieve any rows from the pythonfun table";
        $failure = true;
        header( 'Location: '.addSession('index.php') ) ;
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
?>
<h1>Making a connection from Python</h1>
<p>In this assignment, you will get the code for 
<a href="https://www.pg4e.com/code/simple.py" target="_blank">https://www.pg4e.com/code/simple.py</a>
working.  Download these files using <b>wget</b>, <b>curl</b> or your browser.  If you are using a bash shell
in Linux, these commands might work:
<pre>
wget https://www.pg4e.com/code/simple.py
wget https://www.pg4e.com/code/hidden-dist.py
</pre>
Rename <b>hidden-dist.py</b> to <b>hidden.py</b> - if you are using bash shell in Linux, use the
<b>mv</b> command:
<pre>
mv hidden-dist.py hidden.py
</pre>
Edit <b>hidden.py</b> and put your conection details in the <b>secrets()</b> function.
</p>
<?php pg4e_user_db_form($LAUNCH); ?>
<p>
The <b>simple.py</b> code will load its secrets from <b>hidden.py</b> when you run it.
Don't worry about fixing the "mistake" it is just there to show how bad SQL
causes a traceback.
<pre>
$ <b>python3 simple.py</b>
DROP TABLE IF EXISTS pythonfun CASCADE;
CREATE TABLE pythonfun (id SERIAL, line TEXT);
SELECT id, line FROM pythonfun WHERE id=5;
Found (5, 'Have a nice day 4')
New id 11
SELECT line FROM pythonfun WHERE mistake=5;
Traceback (most recent call last):
  File "simple.py", line 66, in <module>
    cur.execute(sql)
psycopg2.errors.UndefinedColumn: column "mistake" does not exist
LINE 1: SELECT line FROM pythonfun WHERE mistake=5;
                                         ^
</pre>
Once this runs, you should have some records in the <b>pythonfun</b>
table - and this is what the autograder is looking for.  You can check
that the records are in the table by using <b>psql</b> and running:
<pre>
SELECT line FROM pythonfun WHERE line LIKE 'Have a nice%';
</pre>
This is the query that the autograder runs and looks for at least one row.
