<?php

// 05sqlstring.php and 05sqlstop.php are copies
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
$table = "docs01";
$index = "invert01";
if ( strpos(__file__,"stop") ) {
    $stop_words = get_stop_words();
    $gin = get_gin($lines, $stop_words);
    $table = "docs02";
    $index = "invert02";
}

ksort($gin);
// echo("<pre>\n");print_r($gin);echo("</pre>\n");

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {
    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

    $sql = "SELECT keyword, doc_id FROM $index ORDER BY keyword, doc_id;";
    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    $correct = 0;
    $failure = false;
    $found = array();
    $i = 0;
    while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
        $i++;
        $keyword = $row[0];
        $docid = $row[1];

        if ( ! isset($gin[$keyword]) ) {
            $_SESSION['error'] = "Row $i unexpected keyword '$keyword' in document $docid";
            $failure = true;
            break;
        }

        if ( !isset($found[$keyword]) ) $found[$keyword] = 0;
        $found[$keyword]++;
        $correct++;
    }
    $stmt->closeCursor();

    if ( !$failure && count($found) != count($gin) ) {
        $_SESSION['error'] = "Expected ". count($gin). ' keywords in your index, found '.count($found);
        // ksort($found); ksort($gin); echo("<pre>\n"); print_r($found); print_r($gin);echo("</pre>\n");die();
        $failure = true;
    }

    if ( ! $failure ) {
        foreach($found as $keyword => $count ) {
            if ( $count != count($gin[$keyword]) ) {
                $_SESSION['error'] = "Keyword '$keyword' should be in ".count($gin[$keyword]).
                    " documents and was only in ".$count.' documents.';
                $failure = true;
                break;
            }
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
<p>Connection Details:</p>
<?php pg4e_user_db_form($LAUNCH); ?>
<pre>
CREATE TABLE <?= $table ?> (id SERIAL, doc TEXT, PRIMARY KEY(id));

CREATE TABLE <?= $index ?> (
  keyword TEXT,
  doc_id INTEGER REFERENCES <?= $table ?>(id) ON DELETE CASCADE
);
</pre>
<?php if ( $stop_words ) { ?>
<p>
If you already have the above tables created and the documents inserted
from a prior assignment, you can just delete all the rows from 
the reverse index and recreate them following the rules of stop words:
<pre>
DELETE FROM <?= $index ?>;
</pre>
</p>
<?php } ?>
Here are the one-line documents that you are to insert into <b><?= $table ?></b>:
<?php insert_docs($table, $lines); ?>
<?php if ( $stop_words ) { ?>
<p>Here are your <a href="https://www.ranks.nl/stopwords" target="_blank">stop words</a>:
<pre>
CREATE TABLE stop_words (word TEXT unique);

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
<p>
Here is a sample for the first few expected rows of your reverse index:
<?php $max_rows = 10; ?>
<pre>
SELECT keyword, doc_id FROM <?= $index ?> ORDER BY keyword, doc_id LIMIT <?= $max_rows ?>;

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

