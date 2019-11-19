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

$stop_words = false;
if ( strpos(__file__,"stop") ) {
    $stop_words = get_stop_words();
    $gin = get_gin($lines, $stop_words);
}

ksort($gin);
// echo("<pre>\n");print_r($gin);echo("</pre>\n");

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
<h1>Reverse Index 
<?php if ( $stop_words ) echo('(with stop words) '); ?>
in SQL</h1>
<p>In this assignment, you will create a table of documents and then
produce a reverse index 
<?php if ( $stop_words ) echo(' with stop words '); ?>
for those documents that identifies each document
which contains a particular word using SQL.
</p>
<?php if ( ! $stop_words ) { ?>
<p>
FYI: In <i>contrast</i> with the provided sample SQL, you will 
map all the words
in the reverse index to lower case (i.e. Python, PYTHON, and python
should all end up as "python" in the inverted index).
</p>
<?php } ?>
<pre>
CREATE TABLE docs01 (id SERIAL, doc TEXT, PRIMARY KEY(id));

CREATE TABLE invert01 (
  keyword TEXT,
  doc_id INTEGER REFERENCES docs(id) ON DELETE CASCADE
);
</pre>
<?php if ( $stop_words ) { ?>
<p>
If you already have the above tables created and the documents inserted
from a prior assignment, you can just delete all the rows from 
the reverse index and recreate them following the rules of stop words:
<pre>
DELETE FROM invert01;
</pre>
</p>
<?php } ?>
Here are the one-line documents that you are to insert into <b>docs01</b>:
<?php insert_docs('docs01', $lines); ?>
<?php if ( $stop_words ) { ?>
<p>Here are your stop words:
<pre>
INSERT INTO stop_words (word) VALUES 
<?php
$count = 0;
$width = 0;
foreach($stop_words as $stop_word) {
    if ( $count > 0 ) echo(", ");
    $count++;
    $width += strlen($stop_word)+6;
    if ( $width > 70 ) {
        $width = 0;
        echo("\n");
    }
    echo("('".$stop_word."')");
}
echo(";\n");
?>
</pre>
<?php } ?>
<?php pg4e_user_db_form($LAUNCH); ?>
<p>
Here is a sample for the first few expected rows of your reverse index:
<?php $max_rows = 10; ?>
<pre>
SELECT keyword, doc_id FROM invert01 ORDER BY keyword, doc_id LIMIT <?= $max_rows ?>;

keyword    |  doc_id
-----------+--------
<?php
$count = 0;
foreach($gin as $word => $docs) {
    foreach($docs as $doc_id) {
        $count++;
        if ( $count > $max_rows ) break;
        printf("%-10s |    %-5d\n",$word, $doc_id);
    }
    if ( $count > $max_rows ) break;
}
?>
</pre>

<?php
if ( $LAUNCH->user->instructor ) {
    echo("<p><b>Note for Instructors:</b> There is a solution to this assignment in pg4e-solutions/assn</p>\n");
}
?>

