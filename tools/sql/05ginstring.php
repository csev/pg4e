<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";
require_once "text_util.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

// Compute the stuff for the output
$code = getCode($LAUNCH);

$lines = get_lines($code);
$gin = get_gin($lines);

$stop_words = get_stop_words();
$gin_stop = get_gin($lines, $stop_words);
ksort($gin);

// Find the longest word
$word = '';
foreach($gin as $keyword => $docs) {
    if(strlen($keyword) > strlen($word)) $word = $keyword;
}

$sql = "SELECT id, doc FROM docs02 WHERE '{".$word."}' <@ string_to_array(lower(doc), ' ');";

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {
    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    // Get one row
    $failure = false;
    $row = $stmt->fetch(\PDO::FETCH_NUM);
    if ( $row == false ) {
        $_SESSION['error'] = "Unable to retrieve a row with the keyword '$word'";
        $failure = true;
    }

    $found = array();
    $stmt = pg4e_query_return_error($pg_PDO, "EXPLAIN ".$sql);
    if ( ! $stmt ) return;

    echo("<pre>\n");
    while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
        $line = $row[0];
        pg4e_debug_note($pg_PDO, "Explain retrieved: ".$line);

        if ( strpos($line, "Seq Scan") !== false ) {
            $_SESSION['error'] = "EXPLAIN query found 'Seq Scan', check pg4e_debug for details.";
            $failure = true;
        }
    }

    if ( $failure ) {
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
<h1>String Array GIN Index</h1>
<p>In this assignment, you will create a table of documents and then
produce a GIN-based <b>text[]</b> reverse index
for those documents that identifies each document
which contains a particular word using SQL.
</p>
<p>
FYI: In <i>contrast</i> with the provided sample SQL, you will
map all the words
in the GIN index to lower case (i.e. Python, PYTHON, and python
should all end up as "python" in the GIN index).
</p>
<p>
The goal of this assignment is to run these queries:
<pre>
<?= $sql ?>

EXPLAIN <?= $sql ?>
</pre>
and (a) get the correct documents and (b) use the GIN index (i.e. not use a sequential scan).
<pre>
CREATE TABLE docs02 (id SERIAL, doc TEXT, PRIMARY KEY(id));

CREATE INDEX array02 ON docs02 USING gin(...);
</pre>
</p>
<p>
If you get an <b>operator class</b> error on your <b>CREATE INDEX</b> check the instructions
below for the solution.
</p>
<p>
Here are the one-line documents that you are to insert into <b>docs02</b>:
<?php insert_docs('docs02', $lines); ?>
<p>
You should also insert a number of filler rows into the table to make sure
PostgreSQL uses its index:
<pre>
INSERT INTO docs02 (doc) SELECT 'Neon ' || generate_series(10000,20000);
</pre>
</p>
<?php pg4e_user_db_form($LAUNCH); ?>
<h2>Operator Class Errors</h2>
<p>
If you try to create the index using the <b>_text_ops</b> operator class
and it fails as follows switch to using <b>array_ops</b> as the operator class:
<pre>
ERROR:  operator class "_text_ops" does not exist for access method "gin"
</pre>
PostgreSQL 9.6 uses <b>_text_ops</b> and PostgresSQL 11 has collapsed all the
array operator classes into a more flexible <b>array_ops</b> operators
 class. To check to see which version of PostgreSQL you are using, use this command:
<pre>
pg4e=> select version();
                                        version
---------------------------------------------------------------------------------------
 PostgreSQL 11.2 on x86_64-pc-linux-musl, compiled by gcc (Alpine 8.3.0) 8.3.0, 64-bit
</pre>
</p>
<?php
if ( $LAUNCH->user->instructor ) {
    echo("<p><b>Note for Instructors:</b> There is a solution to this assignment in pg4e-solutions/assn</p>\n");
}
?>

