<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";
require_once "text_util.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

// Compute the stuff for the output
$code = getCode($LAUNCH);

$names = array( 'bulbasaur', 'caterpie', 'rattata', 'ekans',
    'nidorina', 'nidoking', 'jigglypuff', 'zubat', 'venomoth',
    'poliwag', 'geodude', 'kingler', 'slowbro');

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {
    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

    foreach($names as $name) {
        $sql = "SELECT COUNT(*) FROM pokeapi WHERE (body->>'name')::text = '$name'";
        $stmt = pg4e_query_return_error($pg_PDO, $sql);
        if ( ! $stmt ) return;

        // Get one row
        $failure = false;
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        if ( $row == false ) {
            $_SESSION['error'] = "Unable to retrieve a row with the name '$name'";
            $failure = true;
            header( 'Location: '.addSession('index.php') ) ;
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
<h1>Loading JSON from PokeAPI</h1>
<p>In this assignment, you will ...
</p>
<p>
<pre>
CREATE TABLE IF NOT EXISTS pokeapi (id INTEGER, body JSONB);

https://pokeapi.co/api/v2/pokemon/1
</pre>
<?php pg4e_user_db_form($LAUNCH); ?>

<?php
if ( $LAUNCH->user->instructor ) {
    echo("<p><b>Note for Instructors:</b> There is a solution to this assignment in pg4e-solutions/assn</p>\n");
}
?>

