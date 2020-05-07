<?php
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

require_once "sql_util.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

$oldgrade = $RESULT->grade;

// Compute the stuff for the output
$code = getCode($LAUNCH);
$answer = makeRoster($code,3,5);

function compare_func($a, $b) {
    // Course
    if ( $a[1] < $b[1] ) return -1;
    if ( $a[1] > $b[1] ) return 1;
    // Role (1 comes first)
    if ( $a[2] < $b[2] ) return 1;
    if ( $a[2] > $b[2] ) return -1;
    // User
    if ( $a[0] < $b[0] ) return -1;
    if ( $a[0] > $b[0] ) return 1;
    return 0;
}

usort($answer,"compare_func");

$sql = "SELECT student.name, course.title, roster.role
    FROM student 
    JOIN roster ON student.id = roster.student_id
    JOIN course ON roster.course_id = course.id
    ORDER BY course.title, roster.role DESC, student.name;
";

// Grade here
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


if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<h1>Building a many-to-many roster</h1>
<?php pg4e_user_db_form($LAUNCH); ?>
<p>
Create the following tables.
<pre>
CREATE TABLE student (
    id SERIAL,
    name VARCHAR(128) UNIQUE,
    PRIMARY KEY(id)
);

DROP TABLE course CASCADE;
CREATE TABLE course (
    id SERIAL,
    title VARCHAR(128) UNIQUE,
    PRIMARY KEY(id)
);

DROP TABLE roster CASCADE;
CREATE TABLE roster (
    id SERIAL,
    student_id INTEGER REFERENCES student(id) ON DELETE CASCADE,
    course_id INTEGER REFERENCES course(id) ON DELETE CASCADE,
    role INTEGER,
    UNIQUE(student_id, course_id),
    PRIMARY KEY (id)
);
</pre>
</p>
<h1>Course Data</h1>
<p>
You will normalize the following data (each user gets different data), and insert
the following data items into your database, creating and linking all the
foreign keys properly.  Encode instructor with a role of 1 and a learner with a role
of 0.
<pre>
<?php
foreach($answer as $entry) {
    $role = $entry[2] == 1 ? 'Instructor' : 'Learner';
    echo "$entry[0], $entry[1], $role\n";
}
?>
</pre>
</p>
<p>
You can test to see if your data has been entered properly with the following
SQL statement.
<pre>
<?= $sql ?>
</pre>
The order of the data and number of rows that comes back from this query should be the
same as above.  There should be no missing or extra data in your query.
</p>
<?php
if ( ! $USER->instructor ) {
    $OUTPUT->footer();
    return;
}
?>
<h1>Instructor Only Debug</h1>
Here is a set of insert statements to achieve this assignment.
<pre>
<?php
foreach($answer as $entry) {
    echo "INSERT INTO student (name) VALUES ('$entry[0]') ON CONFLICT (name) DO NOTHING;\n";
    echo "INSERT INTO course (title) VALUES ('$entry[1]') ON CONFLICT (title) DO NOTHING;\n";
    echo "INSERT INTO roster (student_id,course_id,role) VALUES
        ( (SELECT id FROM student WHERE name='$entry[0]') ,
          (SELECT id FROM course WHERE title='$entry[1]') , $entry[2] );\n";
}
?>
</pre>
</p>
