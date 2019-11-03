<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$MAX_UPLOAD_FILE_SIZE = 1024*1024;

require_once "sql_util.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

$file = fopen("library.csv","r");
$library = array();
$answer = array();
while ( $pieces = fgetcsv($file) ) {
    $library[] = $pieces;
    $answer[] = array($pieces[0], $pieces[2]);
}
fclose($file);

sort($library);
sort($answer);

$sql = "SELECT title, album FROM track_raw ORDER BY title LIMIT 3;";

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {

    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

    $stmt = pg4e_query_return_error($pg_PDO, $sql);
    if ( ! $stmt ) return;

    $good = 0;
    $pos = 0;
    $error = false;
    $found = false;
    while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
        $ans = $answer[$pos];
        foreach($ans as $i => $txt ) {
            if ($row[$i] != $txt ) {
                $pos++; $col = $i + 1;
                $_SESSION['error'] = "Row $pos column $col expected $txt, got ".$row[$i];
                $error = true;
                break;
            }
        }
        if ( $error ) break;
        $good++;
        $pos++;
    }

    if ( $pos == 0 ) {
        $_SESSION['error'] = "No records found in tracks table";
        $error = true;
    }

    if ( $good < 3 ) {
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    $gradetosend = 1.0;
    pg4e_grade_send($LAUNCH, $pg_PDO, $oldgrade, $gradetosend, $dueDate);

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

if ( $RESULT->grade > 0 ) {
    echo('<p class="alert alert-info">Your current grade on this assignment is: '.($RESULT->grade*100.0).'%</p>'."\n");
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<h1>Musical Track Database</h1>
<p>
This application will read an iTunes library in comma-separated-values (CSV)
and produce a table as specified below.
Once you have placed the proper data in the table, press the button below to
check your answer.
</p>
<?php pg4e_user_db_form($LAUNCH); ?>
<p>
Here is the structure of the tables you will need for this assignment:
<pre>
CREATE TABLE track_raw
 (title TEXT, artist TEXT, album TEXT,
  count INTEGER, rating INTEGER, len INTEGER);
</pre>
We will ignore the artist field for this assignment and focus on the many-to-one relationship
between tracks and albums.
</p>
<p>
If you run the program multiple times in testing or with different files,
make sure to empty out the data before each run.
<p>
Download this file
<a href=library.csv" target="_blank">
<?= $CFG->apphome ?>/tools/sql/library.csv
</a>
data file into the system where you are running your <b>psql</b> client or notebooks. Two commands
to download the file into a terminal or shell are:
<pre>
wget <?= $CFG->apphome ?>/tools/sql/library.csv
curl -O <?= $CFG->apphome ?>/tools/sql/library.csv
</pre>
If you don't have either <b>wget</b> or <b>curl</b> installed you will need to
download the CSV file and upload or copy
it to the folder where you are running your SQL client.
<p>
Once you have the file in the correct location, you can load it
into the <b>track_raw</b> table using the <b>\copy</b> command.
<pre>
\copy track_raw(title,artist,album,count,rating,len) FROM 'library.csv' WITH DELIMITER ',' CSV;
</pre>
<p>
To grade this assignment, the program will run a query like this on
your database and look for the data it expects to see:
<pre>
<?= htmlentities($sql) ?>
</pre>
The expected result of this query on your database is:
<table border="2">
<tr>
<th>title</th><th>album</th>
</tr>
<?php
$pos=0;
foreach($answer as $ans) {
    echo("<tr>");
    foreach($ans as $i => $txt ) {
        echo("<td>".htmlentities($txt)."</td>");
    }
    echo("<tr>\n");
    $pos++;
    if ( $pos >= 3 ) break;
}
?>
</table>
</p>
