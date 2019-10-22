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


$pdo_connection = 'pgsql:host=167.71.95.37;dbname=people';
$pdo_user = 'pg4e';
$pdo_pass = 'secret';

// $PDO = new PDOX('pgsql:host=167.71.95.37;dbname=people', 'pg4e', 'secret');
/*
$pg_PDO = new PDOX($pdo_connection, $pdo_user, $pdo_pass);
$row = $pg_PDO->rowDie("SELECT 1 As Test");
var_dump($row);
die();
 */

$answer = array(
  array(
    "Chase the Ace",
    "AC/DC",
    "Who Made Who",
    "Rock"
  ),
  array(
    "D.T.",
    "AC/DC",
    "Who Made Who",
    "Rock"
  ),
  array(
    "For Those About To Rock (We Salute You)",
    "AC/DC",
    "Who Made Who",
    "Rock"
  )
);

$sql = "SELECT Track.title, Artist.name, Album.title, Genre.name
    FROM Track 
    JOIN Genre ON Track.genre_id = Genre.id
    JOIN Album ON Track.album_id = Album.id
    JOIN Artist ON Album.artist_id = Artist.id
    ORDER BY Artist.name LIMIT 3";

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
    while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
        $ans = $answer[$pos];
        foreach($ans as $i => $txt ) {
            if ($row[$i] != $txt ) {
                $_SESSION['error'] = "Row $pos column $i expected $txt, got ".$row[$i];
                break;
            }
        }
        $good++;
        $pos++;
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
<p>
<form name="myform" enctype="multipart/form-data" method="post" >
To get credit for this assignment, perform the instructions below and 
upload your SQLite3 database here: <br/>
<input type="submit" name="check" value="Check Answer">
<p>
You do not need to export or convert the database -  simply upload 
the <b>.sqlite</b> file that your program creates.  See the example code for
the use of the <b>connect()</b> statement.
</p>
</form>
</p>
<h1>Musical Track Database</h1>
<p>
This application will read an iTunes export file in XML and produce a properly
normalized database with this structure:
<pre>
CREATE TABLE artist (
  id SERIAL,
  name VARCHAR(128) UNIQUE,
  PRIMARY KEY(id)
);

CREATE TABLE album (
  id SERIAL,
  title VARCHAR(128) UNIQUE,
  artist_id INTEGER REFERENCES artist(id) ON DELETE CASCADE,
  PRIMARY KEY(id)
);

CREATE TABLE genre (
  id SERIAL,
  name VARCHAR(128) UNIQUE,
  PRIMARY KEY(id)
);

CREATE TABLE track (
    id SERIAL,
    title VARCHAR(128),
    len INTEGER, rating INTEGER, count INTEGER,
    album_id INTEGER REFERENCES album(id) ON DELETE CASCADE,
    genre_id INTEGER REFERENCES genre(id) ON DELETE CASCADE,
    UNIQUE(title, album_id),
    PRIMARY KEY(id)
);
</pre>
</p>
<p>
If you run the program multiple times in testing or with different files, 
make sure to empty out the data before each run.
<p>
You can use this code as a starting point for your application:
<a href="http://www.pythonlearn.com/code/tracks.zip" target="_blank">
http://www.pythonlearn.com/code/tracks.zip</a>.  
The ZIP file contains the <b>Library.xml</b> file to be used for this assignment.
You can export your own tracks from iTunes and create a database, but
for the database that you turn in for this assignment, only use the 
<b>Library.xml</b> data that is provided.
</p>
<p>
To grade this assignment, the program will run a query like this on
your uploaded database and look for the data it expects to see:
<pre>
<?= htmlentities($sql) ?>
</pre>
The expected result of this query on your database is:
<table border="2">
<tr>
<th>Track</th><th>Artist</th><th>Album</th><th>Genre</th>
</tr>
<?php
foreach($answer as $ans) {
    echo("<tr>");
    foreach($ans as $i => $txt ) {
        echo("<td>".htmlentities($txt)."</td>");
    }
    echo("<tr>\n");
}
?>
</p>
