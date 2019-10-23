<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$MAX_UPLOAD_FILE_SIZE = 1024*1024;

require_once "sql_util.php";

// $myPDO = new PDO('pgsql:host=localhost;dbname=DBNAME', 'USERNAME', 'PASSWORD');
// %sql postgres://pg4e:secret@167.71.95.37:5432/people

$pdo_host = '167.71.95.37';
$pdo_database = 'people';
$pdo_connection = "pgsql:host=$pdo_host;dbname=$pdo_database";
$pdo_user = 'pg4e';
$pdo_pass = 'secret';

// $PDO = new PDOX('pgsql:host=167.71.95.37;dbname=people', 'pg4e', 'secret');
/*
$pg_PDO = new PDOX($pdo_connection, $pdo_user, $pdo_pass);
$row = $pg_PDO->rowDie("SELECT 1 As Test");
var_dump($row);
die();
 */

$file = fopen("library.csv","r");
$titles = "";
$library = array();
$answer = array();
while ( $pieces = fgetcsv($file) ) {
    if ( strlen($titles) > 0 ) $titles .= ',';
    $titles .= $pieces[0];
    $library[] = $pieces;
    $answer[] = array($pieces[0], $pieces[2]); 
}
fclose($file);
$title_md5 = md5($titles);

sort($library);
sort($answer);

$sql = "SELECT Track.title, Album.title
    FROM Track
    JOIN Album ON Track.album_id = Album.id
    ORDER BY Track.title LIMIT 3;";

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {

    $pg_PDO = new PDOX($pdo_connection, $pdo_user, $pdo_pass);
    $stmt = $pg_PDO->queryReturnError($sql);
    if ( ! $stmt->success ) {
        error_log("Sql Failure:".$stmt->errorImplode." ".$sql);
        $_SESSION['error'] = "SQL Query Error: ".$stmt->errorImplode;
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

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
and produce a properly normalized database here:
<pre>
Host: <?= $pdo_host ?>

Database: <?= $pdo_database ?>

Account: <?= $pdo_user ?>

Password: <span id="pass" style="display:none"><?= $pdo_pass ?></span> (<a href="#" onclick="$('#pass').toggle();return false;">hide/show</a>)
</pre>
</p>
<p>
Once you have placed the proper data in the database, press the button below to
check your answer.
<form name="myform" method="post" >
<input type="submit" name="check" value="Check Answer">
</form>
</p>
<p>
Here is the structure of your database:
<pre>
CREATE TABLE album (
  id SERIAL,
  title VARCHAR(128) UNIQUE,
  artist_id INTEGER REFERENCES artist(id) ON DELETE CASCADE,
  PRIMARY KEY(id)
);

CREATE TABLE track (
    id SERIAL,
    title VARCHAR(128),
    len INTEGER, rating INTEGER, count INTEGER,
    album_id INTEGER REFERENCES album(id) ON DELETE CASCADE,
    UNIQUE(title, album_id),
    PRIMARY KEY(id)
);
</pre>
We will ignore the artist field for this assignment and focus on the many-to-onr replationship
between tracks and albums.
</p>
<p>
If you run the program multiple times in testing or with different files, 
make sure to empty out the data before each run.
<p>
Here is the 
<a href=library.csv" target="_blank">
CSV data 
</a>
for the application.
</p>
<p>
To grade this assignment, the program will run a query like this on
your database and look for the data it expects to see:
<pre>
<?= htmlentities($sql) ?>
</pre>
The expected result of this query on your database is:
<table border="2">
<tr>
<th>Track</th><th>Album</th>
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
<p>
Also run the following query on your table:
<pre>
select md5(string_agg(title, ',')) from track;

Result: <?= $title_md5 ?>
</pre>
