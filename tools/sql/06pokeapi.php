<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;
use \Tsugi\Grades\GradeUtil;

require_once "names.php";
require_once "text_util.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

$config = getCourseSettings();

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
        if ( ! is_array($row) || $row[0] < 1 ) {
            $_SESSION['error'] = "Unable to retrieve a row with the name '$name'";
            $failure = true;
            header( 'Location: '.addSession('index.php') ) ;
            return;
        }
    }

    $stmt->closeCursor();

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
<h1>Loading JSON from PokéAPI</h1>
<p>In this assignment, you will load the first 100 Pokémon JSON documents from the 
<a href="https://pokeapi.co/" target="_blank">PokéAPI</a> and store them in a table.
</p>
<?php
$endform = false;
pg4e_user_db_form($LAUNCH, $endform);
?>
<p>
Here is the table to create:
<pre>
CREATE TABLE IF NOT EXISTS pokeapi (id INTEGER, body JSONB);
</pre>
</p>
<p>
This assignment is <b>not</b> an adaptation of any of the sample code
we provide.  You will probably have to write this assignment from scratch.
You can look at the code for
<a href="https://www.pg4e.com/code/swapi.py" target="_blank">https://www.pg4e.com/code/swapi.py</a>
and use it to help you write your application - 
but your application will be much simpler than the <b>swapi.py</b>
application.
</p>
<p>
What you need to do is loop through the <a href="https://pokeapi.co/" target="_blank">PokéAPI</a> and retrieve the JSON data for urls ending in 1..100 and store it in the above table.   
Here is an example URL that ends in <code>1</code>: <code>https://pokeapi.co/api/v2/pokemon/1</code>.  You may find that the Pokemon API supports other 
URL patterns to retrieve the data, which is fine!  But a simple approach wouuld be to loop through the numbers 1..100 and modify this URL to retrieve 
the data for that item.
</p>
<p>The autograder will run a query that looks for data from the API has been successfully inserted into rows in the <code>pokeapi</code> table.</p>
<p>
There is no need to have a status field or have a restartable process for this assignment.  
Just get the first 100 JSON items and store them in the above table.
</p>
<p>Optionally, but not required by the autograder, feel free to paste your Python code in the space below the assignment to share with reviewers.</p>
<?php
if ( $LAUNCH->user->instructor ) {
    echo("<p><b>Note for Instructors:</b> There is a solution to this assignment in pg4e-solutions/assn</p>\n");
}
?>
<p>
Please enter your Python code here:
<textarea id="code" name="code" style="width:100%; height: 100%; font-family:Courier,fixed;font-size:16px;color:blue;">
<?php
if ( U::get($_SESSION, 'lastcode')){
    echo(htmlentities(U::get($_SESSION, 'lastcode')));
}
?>
</textarea>
</form>
</p>
