<?php

use \Tsugi\Util\U;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\Mersenne_Twister;
use \Tsugi\Core\LTIX;

require_once "names.php";
require_once "courses.php";

function makeRoster($code,$course_count=false,$name_count=false) {
    global $names, $courses;
    $MT = new Mersenne_Twister($code);
    $retval = array();
    $cc = 0;
    foreach($courses as $k => $course) {
    $cc = $cc + 1;
    if ( $course_count && $cc > $course_count ) break;
        $new = $MT->shuffle($names);
        $new = array_slice($new,0,$MT->getNext(17,53));
        $inst = 1;
        $nc = 0;
        foreach($new as $k2 => $name) {
            $nc = $nc + 1;
            if ( $name_count && $nc > $name_count ) break;
            $retval[] = array($name, $course, $inst);
            $inst = 0;
        }
    }
    return $retval;
}

// Unique to user + course
function getCode($LAUNCH) {
    return $LAUNCH->user->id*42+$LAUNCH->context->id;
}

function getUnique($LAUNCH) {
    return md5($LAUNCH->user->key.'::'.$LAUNCH->context->key.
        '::'.$LAUNCH->user->id.'::'.$LAUNCH->context->id);
}

function getDbName($unique) {
    return substr("pg4e".$unique,0,15);
}

function getDbUser($unique) {
    return "pg4e_user_".substr($unique,15,5);
}

function getDbPass($unique) {
    return "pg4e_pass_".substr($unique,20,5);
}

/**
 * Returns
 * Object if good JSON was recceived.
 * String if something went wrong
 * Number if something went wrong and all we have is the http code
 */
function pg4e_request($dbname, $path='info') {
    global $CFG, $pg4e_request_result;

    $pg4e_request_result = false;
    $endpoint = $CFG->pg4e_api_url.'/'.$path.'/'.$dbname;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $CFG->pg4e_api_key.':'.$CFG->pg4e_api_password);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    $pg4e_request_result = curl_exec($ch);
    if($pg4e_request_result === false)
    {
        return 'Curl error: ' . curl_error($ch);
    }
    $returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ( $returnCode != 200 ) return $returnCode;

    // It seems as though create success returns '"" '
    if ( $returnCode == 200 && trim($pg4e_request_result) == '""' ) return 200;

    // Lets parse the JSON
    $retval = json_decode($pg4e_request_result, false);  // As stdClass
    if ( $retval == null ) {
        error_log("JSON Error: ".json_last_error_msg());
        error_log($pg4e_request_result);
        return "JSON Error: ".json_last_error_msg();
    }
    return $retval;
}

function pg4e_extract_info($info) {
    $user = false;
    $password = false;
    $ip = false;
    try {
        $retval = new \stdClass();
         $retval->user = base64_decode($info->auth->data->POSTGRES_USER);
         $retval->password = base64_decode($info->auth->data->POSTGRES_PASSWORD);
         $retval->ip = $info->svc->status->loadBalancer->ingress[0]->ip ?? null;
        return $retval;
    } catch(Exception $e) {
        return null;
    }
}

function pg4e_unlock_code($LAUNCH) {
    global $CFG;
    $unlock_code = md5(getUnique($LAUNCH) . $CFG->pg4e_unlock) ;
        return $unlock_code;
}

function pg4e_unlock_check($LAUNCH) {
    global $CFG;
    if ( $LAUNCH->context->key != '12345' ) return true;
    $unlock_code = pg4e_unlock_code($LAUNCH);
    if ( U::get($_COOKIE, 'unlock_code') == $unlock_code ) return true;
    return false;
}

function pg4e_unlock($LAUNCH) {
    global $CFG, $OUTPUT;
    if ( pg4e_unlock_check($LAUNCH) ) return true;

    $unlock_code = pg4e_unlock_code($LAUNCH);
    if ( U::get($_POST, 'unlock_code') == $CFG->pg4e_unlock ) {
        setcookie('unlock_code', $unlock_code);
        header("Location: ".addSession($_SERVER['REQUEST_URI']));
        return false;
    }
    $OUTPUT->header();
    $OUTPUT->bodyStart(false);
    $OUTPUT->topNav();
    ?>
<form method="post">
<p>Unlock code:
<input type="password" name="unlock_code">
<input type="submit">
</form>
<?php
    $OUTPUT->footer();
    return false;
}

function pg4e_user_db_load($LAUNCH) {
    global $pdo_database, $pdo_host, $pdo_user, $pdo_pass, $info, $pdo_connection;

    if ( U::get($_POST,'default') ) {
                unset($_SESSION['pdo_host']);
                unset($_SESSION['pdo_database']);
                unset($_SESSION['pdo_user']);
                unset($_SESSION['pdo_pass']);
        header( 'Location: '.addSession('index.php') ) ;
                return false;
    }

    $unique = getUnique($LAUNCH);
    $project = getDbName($unique);

    $pdo_database = U::get($_POST, 'pdo_database',U::get($_SESSION,'pdo_database', 'pg4e') );
    $pdo_host = U::get($_POST, 'pdo_host', U::get($_SESSION,'pdo_host'));
    $pdo_user = U::get($_POST, 'pdo_user', U::get($_SESSION,'pdo_user', getDbUser($unique)) );
    $pdo_pass = U::get($_POST, 'pdo_pass', U::get($_SESSION,'pdo_pass', getDbPass($unique)) );

    if ( ! $pdo_host ) {
        $retval = pg4e_request($project, 'info');
        $info = false;
        if ( is_object($retval) ) {
            $info = pg4e_extract_info($retval);
             if ( isset($info->ip) ) $pdo_host = $info->ip;
            $_SESSION['pdo_host'] = $pdo_host;
        }
    }

    if ( $LAUNCH->user->instructor ) {
        $_SESSION['pdo_host'] = $pdo_host;
        $_SESSION['pdo_database'] = $pdo_database;
        $_SESSION['pdo_user'] = $pdo_user;
        $_SESSION['pdo_pass'] = $pdo_pass;
    }

    $pdo_connection = "pgsql:host=$pdo_host;dbname=$pdo_database";

    if ( ! $pdo_host && ! $LAUNCH->user->instructor ) {
        echo("<p>You have not yet set up your database server for project <b>".htmlentities($unique)."</b></p>\n");
        echo("<p>Make sure to run the setup process before attempting this assignment..</p>\n");
        return false;
    }
    return true;
}

function pg4e_user_db_form($LAUNCH) {
    global $OUTPUT, $pdo_database, $pdo_host, $pdo_user, $pdo_pass, $info, $pdo_connection;

        if ( ! $pdo_host || strlen($pdo_host) < 1 ) {
                echo('<p style="color:red">It appears that your PostgreSQL environment is not yet set up or is not running.</p>'."\n");
    }
?>
<form name="myform" method="post" >
<p>
<?php if ( $LAUNCH->user->instructor ) { ?>
Host: <input type="text" name="pdo_host" value="<?= htmlentities($pdo_host) ?>"><br/>
Database: <input type="text" name="pdo_database" value="<?= htmlentities($pdo_database) ?>"><br/>
User: <input type="text" name="pdo_user" value="<?= htmlentities($pdo_user) ?>"><br/>
Password: <span id="pass" style="display:none"><input type="text" name="pdo_pass" value="<?= htmlentities($pdo_pass) ?>"/></span> (<a href="#" onclick="$('#pass').toggle();return false;">hide/show</a>) <br/>
</pre>
<?php } else { ?>
<p>
<pre>
Host: <?= $pdo_host ?>

Database: <?= $pdo_database ?>

Account: <?= $pdo_user ?>

Password: <span id="pass" style="display:none"><?= $pdo_pass ?></span> (<a href="#" onclick="$('#pass').toggle();return false;">hide/show</a>)
</pre>
</p>
<?php } ?>
<input type="submit" name="check" onclick="$('#submitspinner').show();return true;" value="Check Answer">
<img id="submitspinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display:none">
<input type="submit" name="default" value="Default Values">
</form>
</p>
<p>
Access commands:
<pre>
Command line:
psql -h <?= htmlentities($pdo_host) ?> -U <?= htmlentities($pdo_user) ?> <?= htmlentities($pdo_database) ?>


Python Notebook:
%sql postgres://<?= htmlentities($pdo_user) ?>:replacewithsecret@<?= htmlentities($pdo_host) ?>/<?= htmlentities($pdo_database) ?>
</pre>
</p>
<?php
}

function pg4e_insert_meta($pg_PDO, $keystr, $valstr) {
    $pg_PDO->queryReturnError(
        "INSERT INTO pg4e_meta (keystr, valstr) VALUES (:keystr, :valstr)
                ON CONFLICT (keystr) DO UPDATE SET keystr=:keystr, updated_at=now();",
        array(":keystr" => $keystr, ":valstr" => $valstr)
    );
}

function pg4e_get_user_connection($LAUNCH, $pdo_connection, $pdo_user, $pdo_pass) {
    try {
        $pg_PDO = new PDOX($pdo_connection, $pdo_user, $pdo_pass,
        array(
            PDO::ATTR_TIMEOUT => 5, // in seconds
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        )
    );
    } catch(Exception $e) {
        $_SESSION['error'] = "Could not make database connection to ".$pdo_host." / ".$pdo_user
            ." | ".$e->getMessage();
        header( 'Location: '.addSession('index.php') ) ;
        return false;
    }
        return $pg_PDO;
}

function pg4e_check_debug_table($LAUNCH, $pg_PDO) {
    global $CFG;
    $sql = "SELECT id, query, result, created_at FROM pg4e_debug";
    $stmt = $pg_PDO->queryReturnError($sql);
    if ( ! $stmt->success ) {
        error_log("Sql Failure:".$stmt->errorImplode." ".$sql);
        $_SESSION['error'] = "SQL Query Error: ".$stmt->errorImplode;
        header( 'Location: '.addSession('index.php') ) ;
        return false;
    }
    $stmt = $pg_PDO->queryReturnError("DELETE FROM pg4e_debug");
        $stmt = $pg_PDO->queryReturnError(
                "INSERT INTO pg4e_debug (query, result) VALUES (:query, 'Success')",
                array(":query" => $sql)
        );
        pg4e_insert_meta($pg_PDO, "user_id", $LAUNCH->user->id);
        pg4e_insert_meta($pg_PDO, "context_id", $LAUNCH->context->id);
        pg4e_insert_meta($pg_PDO, "key", $LAUNCH->context->key);
    $valstr = md5($LAUNCH->context->key.'::'.$CFG->pg4e_unlock).'::42::'.
                ($LAUNCH->user->id*42).'::'.($LAUNCH->context->id*42);
    $pg_PDO->queryDie(
        "INSERT INTO pg4e_meta (keystr, valstr) VALUES (:keystr, :valstr)
                ON CONFLICT (keystr) DO NOTHING;",
        array(":keystr" => "code", ":valstr" => $valstr)
    );
        return true;
}

function pg4e_debug_note($pg_PDO, $note) {
    $pg_PDO->queryReturnError(
        "INSERT INTO pg4e_debug (query, result) VALUES (:query, :result)",
        array(":query" => $note, ':result' => 'Note only')
    );
}

function pg4e_query_return_error($pg_PDO, $sql, $arr=false) {
    $stmt = $pg_PDO->queryReturnError($sql, $arr);
    if ( ! $stmt->success ) {
                $pg_PDO->queryReturnError(
                        "INSERT INTO pg4e_debug (query, result) VALUES (:query, :result)",
                        array(":query" => $sql, ':result' => $stmt->errorImplode)
                );
        error_log("Sql Failure:".$stmt->errorImplode." ".$sql);
        $_SESSION['error'] = "SQL Query Error: ".$stmt->errorImplode;
        header( 'Location: '.addSession('index.php') ) ;
        return false;
    }
        $pg_PDO->queryReturnError(
                "INSERT INTO pg4e_debug (query, result) VALUES (:query, 'Success')",
                array(":query" => $sql)
        );
        return $stmt;
}

function pg4e_grade_send($LAUNCH, $pg_PDO, $gradetosend, $oldgrade, $dueDate) {
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
        $scorestr = "Grade not sent: ".$retval;
        $_SESSION['error'] = $scorestr;
    } else {
                $scorestr = "Unexpected return: ".json_encode($retval);
                $_SESSION['error'] = "Unexpected return, see pg4e_result for detail";
    }
        $pg_PDO->queryReturnError(
        "INSERT INTO pg4e_result (link_id, score, note, title, debug_log)
                    VALUES (:link_id, :score, :note, :title, :debug_log)",
        array(":link_id" => $LAUNCH->link->id, ":score" => $gradetosend,
               ":note" => $scorestr, ":title" => $LAUNCH->link->title,
               ":debug_log" => json_encode($debug_log)
         )
    );
}

function pg4e_load_csv($filename) {
	$file = fopen($filename,"r");
	$retval = array();
	while ( $pieces = fgetcsv($file) ) {
    	$retval[] = $pieces;
	}
	fclose($file);
    return $retval;
}

