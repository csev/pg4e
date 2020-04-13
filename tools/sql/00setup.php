<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$MAX_UPLOAD_FILE_SIZE = 1024*1024;

require_once "sql_util.php";

// echo("<pre>\n");var_dump($_POST);die('zap');

if ( ! pg4e_user_db_load($LAUNCH) ) return;

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {
    $pg_PDO = pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass);
    if ( ! $pg_PDO ) return;
    if ( ! pg4e_check_debug_table($LAUNCH, $pg_PDO) ) return;

	$sql = "SELECT id, keystr, valstr, created_at, updated_at FROM pg4e_meta";
	if ( ! pg4e_query_return_error($pg_PDO, $sql) ) return;

	// $sql = "SELECT id, link_id, score, title, note, debug_log, created_at, updated_at FROM pg4e_result";
	// if ( ! pg4e_query_return_error($pg_PDO, $sql) ) return;

	$gradetosend = 1.0;
	pg4e_grade_send($LAUNCH, $pg_PDO, $oldgrade, $gradetosend, $dueDate);

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}

$cfg = getUMSIConfig();

$unique = getUnique($LAUNCH);
$db = getDbName($unique);
$user = getDbUser($unique);
$pass = getDbPass($unique);

$admin_user = 'postgres';
$admin_pass = $CFG->psql_root_password ?? false;

$pg_PDO = false;
$pg_error = false;

?>
<h1>Your Database</h1>
<p>
You will need access to a database to use for this course.  This tool creates your database and gives
you an account and password to use to connect to the database.
</p>
<?php
if ( ! $admin_pass ) {
    echo("<p>Not configured to create accounts</p>\n");
    return;
}

// $pdo_connection = "pgsql:host=localhost;port=5432;dbname=postgres;";
$pdo_connection = "pgsql:host=localhost;port=5432";
if ( $admin_pass ) {
    try {
        $pg_PDO = new PDOX($pdo_connection, $admin_user, $admin_pass,
        array(
            PDO::ATTR_TIMEOUT => 5, // in seconds
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        )
    );
    } catch(Exception $e) {
        $pg_PDO = false;
        $pg_error = $e->getMessage();
        error_log('Fail to connect to admin: '.$pg_error);
        echo("<p>Not configured to create accounts</p>\n");
        return;
    }
}

if ( $pg_PDO ) {
    // https://dba.stackexchange.com/questions/45143/check-if-postgresql-database-exists-case-insensitive-way
    // SELECT datname FROM pg_catalog.pg_database WHERE lower(datname) = lower('dbname');
    $row = $pg_PDO->rowDie(
        "SELECT datname FROM pg_catalog.pg_database WHERE datname = :nam",
        array(":nam" => $db)
    );

    if ( $row ) {
        echo("<p>Database is available...</p>\n");
    } else {
        $user_row = $pg_PDO->rowDie("SELECT * FROM pg_user WHERE usename = '$user'");
        var_dump($user_row);
        if ( ! $user_row ) {
            echo("<p>Creating database...</p>\n");
            $sql = "CREATE USER $user WITH PASSWORD '$pass' ";
            $stmt = $pg_PDO->queryReturnError($sql);
            if ( ! $stmt->success ) {
                error_log("Sql Failure:".$stmt->errorImplode." ".$sql);
                echo("<p>SQL Query Error: ".htmlentities($stmt->errorImplode)."</p>");
                return false;
            }
        }
        $sql = "CREATE DATABASE $db WITH OWNER $user";
        $stmt = $pg_PDO->queryReturnError($sql);
        if ( ! $stmt->success ) {
            error_log("Sql Failure:".$stmt->errorImplode." ".$sql);
            echo("<p>SQL Query Error: ".htmlentities($stmt->errorImplode)."</p>");
            return false;
        }
        $row = $pg_PDO->rowDie(
            "SELECT datname FROM pg_catalog.pg_database WHERE datname = :nam",
            array(":nam" => $db)
        );
    }
}
?>
<pre>
Host:     127.0.0.1
Port:     5000
Database: <?= $db ?> 
User:     <?= $user ?> 
Password: <span id="pass" style="display:none"><?= $pass ?></span> (<a href="#" onclick="$('#pass').toggle();return false;">hide/show</a> <a href="#" onclick="copyToClipboard(this, $('#pass').text());return false;">copy</a>)


psql -h 127.0.0.1 -p 5000 -U <?= $user ?> <?= $db ?>
</pre>
