<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$MAX_UPLOAD_FILE_SIZE = 1024*1024;

require_once "sql_util.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

//   0       1            2         3     4          5         6           7       8     9     10
// name,description,justification,year,longitude,latitude,area_hectares,category,state,region,iso

// For some randomness
$code = getCode($LAUNCH);
$order_fields = [
   array('year',1),
   array('category.name',2),
   array('state.name',3),
   array('region.name',4),
   array('iso.name',5),
];

$order_field = $order_fields[$code % count($order_fields)][0];
$order_pos = $order_fields[$code % count($order_fields)][1];

$file = fopen("whc-sites-2018-small.csv","r");
$answer = array();
$first = true;
while ( $pieces = fgetcsv($file) ) {
    if ( $first ) {
        $first = false;
        continue;
    }
    $answer[] = array($pieces[0], $pieces[3], $pieces[7], $pieces[8], $pieces[9], $pieces[10]);
}
fclose($file);

// Two key sort
function compare_row($x, $y) {
    global $order_pos;

    $xcol = trim($x[$order_pos]);
    $ycol = trim($y[$order_pos]);
    if ( strlen($xcol) == 0 && strlen($ycol) > 0 ) return 1;
    if ( strlen($xcol) > 0 && strlen($ycol) == 0 ) return 1;

    if ($xcol > $ycol ) return 1;
    if ($xcol < $ycol) return -1;

    if ($x[0] > $y[0]) return 1;
    if ($x[0] < $y[0]) return -1;

    return 0;
}

usort($answer, "compare_row");

$sql = "SELECT unesco.name, year, category.name, state.name, region.name, iso.name
  FROM unesco
  JOIN category ON unesco.category_id = category.id
  JOIN iso ON unesco.iso_id = iso.id
  JOIN state ON unesco.state_id = state.id
  JOIN region ON unesco.region_id = region.id
  ORDER BY $order_field, unesco.name
  LIMIT 3;
";

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
    $stmt->closeCursor();

    if ( $pos == 0 ) {
        $_SESSION['error'] = "No records found in unesco table";
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
<h1>Unesco Heritage Sites Many-to-One</h1>
<p>
In this assignment you will read some
<a href="https://whc.unesco.org/en/list/" target="_blank" class="btn btn-primary" >Unesco Heritage Site</a> data
in comma-separated-values (CSV) format
and produce properly normalized tables as specified below.
Once you have placed the proper data in the tables, press the button below to
check your answer.
</p>
<?php pg4e_user_db_form($LAUNCH, true); ?>
<p>
Here is the structure of the tables you will need for this assignment:
<pre>
DROP TABLE unesco_raw;
CREATE TABLE unesco_raw
 (name TEXT, description TEXT, justification TEXT, year INTEGER,
    longitude FLOAT, latitude FLOAT, area_hectares FLOAT,
    category TEXT, category_id INTEGER, state TEXT, state_id INTEGER,
    region TEXT, region_id INTEGER, iso TEXT, iso_id INTEGER);

CREATE TABLE category (
  id SERIAL,
  name VARCHAR(128) UNIQUE,
  PRIMARY KEY(id)
);

... More tables needed
</pre>
To load
<a href=whc-sites-2018-small.csv" download>
the CSV data for this assignment
</a>
use the following <b>copy</b> command.  Adding <b>HEADER</b> causes the CSV loader
to skip the first line in the CSV file.  The <b>\copy</b> command must be one long line.
<pre>
\copy unesco_raw(name,description,justification,year,longitude,latitude,area_hectares,category,state,region,iso) FROM 'whc-sites-2018-small.csv' WITH DELIMITER ',' CSV HEADER;
</pre>
Normalize the data in the <b>unesco_raw</b> table by adding the entries to each of
the lookup tables (category, etc.) and then adding the foreign key columns
to the <b>unesco_raw</b> table.  Then make a new table called <b>unesco</b> that
removes all of the un-normalized redundant text columns like <b>category</b>.
</p>
<p>
If you run the program multiple times in testing or with different files,
make sure to empty out the data before each run.
</p>
<p>
The autograder will look at the <b>unesco</b> table.
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
<th>Name</th><th>Year</th><th>Category</th><th>State</th><th>Region</th><th>iso</th>
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
