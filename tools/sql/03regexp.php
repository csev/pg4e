<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";

$problems = array(
    array('^[0-9][0-9] ', 'Lines that start with 2 digits followed by a space'),
    array('[0-9][0-9][0-9][0-9]$', 'Lines that end with with 4 or more digits'),
    array('\([0-9][0-9][0-9]\)', "Lines that have three digit numbers in parenthesis like (567)  - remember to escape certain characters :)"),
    array('\([A-Z][A-Z][A-Z]\)', "Lines that have three upper case characters in parenthesis like (LTI) - remember to escape certain characters :)"),
    array('^[A-Z ]*$', 'Lines that are all upper case letters and spaces and nothing else'),
    array('[:,;]', 'Lines that include a colon (:), comma, or semicolon (;) anywhere in the line'),
    array('\?$', "Lines that end with a question mark? (don't forget to escape)"),
    array('\.$', "Lines that end with a period. (don't forget to escape)"),
    array('^[A-Z][a-z]', "Lines where the first character is upper case and the second character is lower case"),
);

// Randomize
$code = getCode($LAUNCH);
$entry = $problems[($code % count($problems))];
$regex = $entry[0];
$message = $entry[1];
$sql = "SELECT purpose FROM taxdata WHERE purpose ~ '$regex' ORDER BY purpose DESC LIMIT 3";

$readonly_pdo = pg4e_get_readonly_connection();
if ( ! $readonly_pdo ) {
    echo('The readonly database connection is not working.');
    return;
}


// Don't use pg4e_query_return_error because it auto-redirects :(
$stmt = $readonly_pdo->queryReturnError($sql);
if ( ! $stmt->success ) {
    echo("Could not run solution SQL against readonly database.\n");
    if ( $LAUNCH->user->instructor ) {
        echo("<br/>\n");
        echo(htmlentities($sql));
    }
    return;
}

$rows = $stmt->fetchAll();
$solution = array();
foreach($rows as $row ) {
    $solution[] = $row['purpose'];
}

$oldgrade = $RESULT->grade;
$query = U::get($_POST, 'query');
if ( strlen($query) > 0 ) {
    // Don't trim this - a space might be meaningful
    $query = U::get($_POST, 'query');
    if ( stripos($query, 'drop table') !== false ||
         stripos($query, '"') !== false || stripos($query, "'") !== false ) {
        header('Location: https://imgs.xkcd.com/comics/exploits_of_a_mom.png');
        return;
    }

    if ( stripos($query, 'select') !== false ) {
        $_SESSION['error'] = "You don't need to write en entire SQL statement - just the regular expression";
        header('Location: '.addSession('index.php'));
        return;
    }

    if ( strlen($query) > 30 ) {
        $_SESSION['error'] = 'Why is your regular expression so long?';
        header('Location: '.addSession('index.php'));
        return;
    }

    $student_sql = "SELECT purpose FROM taxdata WHERE purpose ~ '$query' ORDER BY purpose DESC LIMIT 3";

    $stmt = $readonly_pdo->queryReturnError($student_sql);
    if ( ! $stmt || ! $stmt->success ) {
        $_SESSION['error'] = "SQL Failed: ".$student_sql."; ".$stmt->errorImplode;
        header('Location: '.addSession('index.php'));
        return;
    }

    $student = array();
    $count = 0;
    while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
        $count = $count + 1;
        if ( $count > 3 ) {
            $_SESSION['error'] = "SQL returned too many rows: ".$query;
            header('Location: '.addSession('index.php'));
            return;
        }
        $student[] = $row['purpose'];
    }
    $stmt->closeCursor();

    // Unlikely
    if ( count($solution) != count($student) ) {
        $_SESSION['error'] = "Expecting ".count($solution)." rows, received ".count($student)." rows";
        header('Location: '.addSession('index.php'));
        return;
    }

    for($i=0; $i<count($solution); $i++) {
        if ( $solution[$i] != $student[$i] ) {
            $_SESSION['error'] = "Mismatch at position ".($i+1)."\n"."Expecting: ".$solution[$i]."\n"."Received: ".$student[$i];
            header('Location: '.addSession('index.php'));
            return;
        }
    }

    $gradetosend = 1.0;
    $pg_PDO = false;  // Don't store in debug
    pg4e_grade_send($LAUNCH, $pg_PDO, $oldgrade, $gradetosend, $dueDate);

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

// Don't allow unhandled posts.
if ( count($_POST) > 0 ) {
    $_SESSION['error'] = "Please enter a regular expression";
    header('Location: '.addSession('index.php'));
    return;
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<h1>Regular Expressions </h1>
<p>In this assignment you will create a regular expression to retrieve
a subset data from the <b>purpose</b> column  of the <b>taxdata</b> table
in the readonly database (access details below).
Write a regular expressions to retrieve that meet the following criteria:
</p>
<p>
<b><?= htmlentities($message) ?></b>
</p>
<p>
As an example (not the solution to this assignment), if you were looking for lines where the <i>very first</i>
character was an upper case character letter you would run the following query:
<pre>
SELECT purpose FROM taxdata WHERE purpose ~ '<b>^[A-Z]</b>' ORDER BY purpose DESC LIMIT 3;
</pre>
The autograder will add all the SQL - all you need is to enter the appropriate regular expression
below.
<p>
<form method="post">
<p>
<input type="text" name="query" style="width:95%;" value="<?= htmlentities($query) ?>" placeholder="Enter a regular expression like ^[A-Z]...">
</p>
<input type="submit" id="submitbutton" class="btn btn-primary" name="submit" onclick="$('#submitbutton').hide();$('#submitspinner').show();return true;" value="Submit Query">
<img id="submitspinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display:none">
</form>
</p>
<p>
Here are the first few lines:
<ol>
<?php 
foreach($solution as $purpose) {
    echo("<li>".htmlentities($purpose)."</li>\n");
}
?>
</ol>
<p>Here are access details for a readonly database:</p>
<?php pg4e_readonly_data($LAUNCH); ?>
<p>Here is the schema for the <b>taxdata</b> table:</p>
<?php taxdata_schema($LAUNCH); ?>
<?php
if ( $LAUNCH->user->instructor ) {
    echo("\n<p>Instructor cheat code: ".htmlentities($sql).";</p>\n");
}
