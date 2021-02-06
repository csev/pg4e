<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

// Randomize
$code = getCode($LAUNCH);
$sorts = Array('ASC', 'DESC', '');
$sort = $sorts[$code % 3];
$columns = array('city', 'state');
$column = $columns[$code % 2];

$column = 'state';
$sort = 'ASC';

$sql = "SELECT DISTINCT $column FROM taxdata";
if ($sort == 'ASC') {
    $sql .= " ORDER BY $column";
    $message = "in ascending order";
} else if ( $sort == 'DESC' ) {
    $sql .= " ORDER BY $column DESC";
    $message = "in descending order";
} else {
    $message = "without an ORDER BY clause (i.e. return rows based on their natural order in the table)";
}
$sql .= ' LIMIT 5';
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
    $solution[] = $row[$column];
}

$oldgrade = $RESULT->grade;
$query = U::get($_POST, 'query');
if ( strlen($query) > 0 ) {
    $query = U::get($_POST, 'query');
    if ( stripos($query, 'drop table') !== false ) {
        header('Location: https://imgs.xkcd.com/comics/exploits_of_a_mom.png');
        return;
    }
    $retval = pg4e_check_user_query($query);
    if ( is_string($retval) ) {
        $_SESSION['error'] = $retval;
        header('Location: '.addSession('index.php'));
        return;
    }

    if ( stripos($query, 'limit 5') === false ) {
        $_SESSION['error'] = 'Missing LIMIT 5 clause';
        header('Location: '.addSession('index.php'));
        return;
    }

    if ( stripos($query, '*') !== false ) {
        $_SESSION['error'] = 'Cannot select all columns (*)';
        header('Location: '.addSession('index.php'));
        return;
    }

    if ( stripos($query, $column) === false ) {
        $_SESSION['error'] = "Query must select the $column column";
        header('Location: '.addSession('index.php'));
        return;
    }

    $stmt = $readonly_pdo->queryReturnError($query);
    if ( ! $stmt || ! $stmt->success ) {
        $_SESSION['error'] = "SQL Failed: ".$query;
        header('Location: '.addSession('index.php'));
        return;
    }

    $student = array();
    $count = 0;
    while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
        $count = $count + 1;
        if ( $count > 5 ) {
            $_SESSION['error'] = "SQL returned too many rows: ".$query;
            header('Location: '.addSession('index.php'));
            return;
        }
        $student[] = $row[$column];
    }
    $stmt->closeCursor();

    if ( count($solution) != count($student) ) {
        $_SESSION['error'] = "Expecting ".count($solution)." rows, received ".count($student)." rows";
        header('Location: '.addSession('index.php'));
        return;
    }

    for($i=0; $i<count($solution); $i++) {
        if ( $solution[$i] != $student[$i] ) {
            $_SESSION['error'] = "Expecting ".$solution[$i]." at position ".($i+1).", received ".$student[$i];
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

// Don't allow unhandled posts.
if ( count($_POST) > 0 ) {
    $_SESSION['error'] = "Please enter a query";
    header('Location: '.addSession('index.php'));
    return;
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<h1>Using SELECT DISTINCT</h1>
<p>In this assignment you are to find the distinct values
in the <b><?= $column ?></b> column  of the <b>taxdata</b> table <?= $message ?>.
Your query should only return these five rows (i.e. inclide a LIMIT clause):
<pre>
<?php 
foreach($solution as $city) {
    echo(htmlentities($city)."\n");
}
?>
</pre>
<p>
<form method="post">
<p>
<input type="text" name="query" style="width:95%;" placeholder="Enter SQL here...">
</p>
<input type="submit" id="submitbutton" class="btn btn-primary" name="submit" onclick="$('#submitbutton').hide();$('#submitspinner').show();return true;" value="Submit Query">
<img id="submitspinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display:none">
</form>
</p>
<p>Here are access details for a readonly database:</p>
<?php pg4e_readonly_data($LAUNCH); ?>
<p>Here is the schema for the <b>taxdata</b> table:</p>
<?php taxdata_schema($LAUNCH); ?>
<?php
if ( $LAUNCH->user->instructor ) {
    echo("\n<p>Instructor cheat code: ".htmlentities($sql).";</p>\n");
}
