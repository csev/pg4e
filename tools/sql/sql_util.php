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

function getUserInfo($LAUNCH) {
    global $CFG;

    $retval = new \stdclass();
    $unique = getUnique($LAUNCH);
    $retval->db = getDbName($unique);
    $retval->user = getDbUser($unique);
    $retval->pass = getDbPass($unique);

    $default_host = 'pg.pg4e.com';
    $default_port = '5432';

    $cfg = getConfig();
    if ( $cfg && isset($cfg->pg_host) && strlen($cfg->pg_host) > 0 ) $default_host = $cfg->pg_host;
    if ( $cfg && isset($cfg->pg_port) && strlen($cfg->pg_port) > 0 ) $default_port = $cfg->pg_port;

    $retval->host = $default_host;
    $retval->port = $default_port;

    $retval->psql = "psql -h $retval->host -p $retval->port -U $retval->user $retval->db";

    $retval->connection = "pgsql:host=$retval->host;port=$retval->port;dbname=$retval->db";
    $retval->local_connection = "pgsql:host=localhost;port=5432;dbname=$retval->db";

    return $retval;
}

function getDbName($unique) {
    return substr("pg4e_".$unique,0,10);
}

function getDbUser($unique) {
    return "pg4e_u_".substr($unique,15,8);
}

function getEsUser($unique) {
    return "pg4e_".substr($unique,12,7);
}

function getCourseSettings() {
    global $TSUGI_LAUNCH;

    $settings = $TSUGI_LAUNCH->context->settingsGetAll();
    return $settings;
}

function getConfig() {
    global $CFG;

    $settings = getCourseSettings();

    $retval = new \stdclass();

    $retval->pg_host = U::get($settings, 'pg_host');
    $retval->pg_port = U::get($settings, 'pg_port');

    $retval->es_source = U::get($settings, 'es_source');
    $retval->es_host = U::get($settings, 'es_host');
    $retval->es_prefix = U::get($settings, 'es_prefix');
    $retval->es_port = U::get($settings, 'es_port');
    $retval->es_password = U::get($settings, 'es_password');
    return $retval;
}

function getDbPass($unique) {
    return "pg4e_p_".substr($unique,20,8);
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

// TODO: Remove
// Really?? Is is used a lot
function pg4e_user_db_load($LAUNCH) {

    // Transport the request to check past the redirect :)
    if ( U::get($_POST,'check') ) {
        $_SESSION['check'] = $_POST['check'];
    }
    // Returns true if redirected
    $retval = pg4e_user_db_post($LAUNCH);
    if ( $retval ) return false;

    // Restore check after redirect if one happens
    if ( U::get($_SESSION,'check') ) {
        $_POST['check'] = $_SESSION['check'];
        unset($_SESSION['check']);
    }

    // Set global values and cookies, etc.
    pg4e_user_db_data($LAUNCH);
    return true;
}

// https://stackoverflow.com/questions/4694089/sending-browser-cookies-during-a-302-redirect
function redirect_200($url) {
?>
<html>
<head><meta http-equiv="refresh" content=0;url="<?=$url?>"></head>
<body><a href="<?=$url?>" style="text-decoration: none;">...</a></body>
</html>
<?php
}

// Handle incoming POST request, redirecting if necessary
function pg4e_user_db_post($LAUNCH) {
    global $CFG;
    global $pdo_database, $pdo_host, $pdo_port, $pdo_user, $pdo_pass, $info, $pdo_connection;

    if ( U::get($_POST,'default') ) {
        unset($_SESSION['pdo_host']);
        unset($_SESSION['pdo_port']);
        unset($_SESSION['pdo_database']);
        unset($_SESSION['pdo_user']);
        unset($_SESSION['pdo_pass']);
        setcookie("pdo_database", '', time()+31556926 ,'/');
        setcookie("pdo_host", '', time()+31556926 ,'/');
        setcookie("pdo_port", '', time()+31556926 ,'/');
        setcookie("pdo_user", '', time()+31556926 ,'/');
        setcookie("pdo_pass", '', time()+31556926 ,'/');
        setcookie("pg4e_desc", '', time()+31556926 ,'/');
        setcookie("pg4e_host", '', time()+31556926 ,'/');
        setcookie("pg4e_port", '', time()+31556926 ,'/');
        //die('yada');
        redirect_200(addSession('index.php'));
        return true;
    }

    // If we have new values... copy them into SESSION
    $retval = false;
    foreach(array('pdo_database', 'pdo_host', 'pdo_port', 'pdo_user', 'pdo_pass') as $key) {
        $value = U::get($_POST, $key);
        if ( ! $value ) continue;
        $_SESSION[$key] = $value;
        $retval = true;
    }
    if ( $retval ) header( 'Location: '.addSession('index.php') ) ;
    return $retval;
}

// The instructor precedence with config is SESSION, COOKIE, CONFIG
// The student precedence with config is SESSION, CONFIG
// Without config, the precedence is SESSION, COOKIE
function pg4e_user_db_data($LAUNCH) {
    global $CFG;
    global $pdo_database, $pdo_host, $pdo_port, $pdo_user, $pdo_pass, $info, $pdo_connection;

    $unique = getUnique($LAUNCH);
    $project = getDbName($unique);

    $user_info = getUserInfo($LAUNCH);

    // defaults
    $pdo_database = U::get($_SESSION, 'pdo_database', U::get($_COOKIE, 'pdo_database', $user_info->db));
    $pdo_host = U::get($_SESSION, 'pdo_host', U::get($_COOKIE, 'pdo_host', $user_info->host));
    $pdo_port = U::get($_SESSION, 'pdo_port', U::get($_COOKIE, 'pdo_port', $user_info->port));
    $pdo_user = U::get($_SESSION, 'pdo_user', U::get($_COOKIE, 'pdo_user', $user_info->user));
    $pdo_pass = U::get($_SESSION, 'pdo_pass', U::get($_COOKIE, 'pdo_pass', $user_info->pass));

    // Store in the database...
    $json = $LAUNCH->result->getJSON();
    $new = json_encode(array(
        'pdo_host' => $pdo_host,
        'pdo_port' => $pdo_port,
        'pdo_database' => $pdo_database,
        'pdo_user' => $pdo_user,
        'pdo_pass' => $pdo_pass,
    ));
    if ( $new != $json ) $LAUNCH->result->setJSON($new);


    // If we have a full set - store the cookies for good measure
    if ( $pdo_database && $pdo_host &&  $pdo_port && $pdo_user && $pdo_pass ) {
        setcookie("pdo_database", $pdo_database, time()+31556926 ,'/');
        setcookie("pdo_host", $pdo_host, time()+31556926 ,'/');
        setcookie("pdo_port", $pdo_port, time()+31556926 ,'/');
        setcookie("pdo_user", $pdo_user, time()+31556926 ,'/');
        setcookie("pdo_pass", $pdo_pass, time()+31556926 ,'/');

        // Cookies for phppgadmin
        setcookie("pg4e_desc", $pdo_database, time()+31556926 ,'/');
        setcookie("pg4e_host", $pdo_host, time()+31556926 ,'/');
        setcookie("pg4e_port", $pdo_port, time()+31556926 ,'/');
    }

    $pdo_connection = "pgsql:host=$pdo_host;port=$pdo_port;dbname=$pdo_database";
}

function pg4e_user_db_form($LAUNCH, $terminalonly=false) {
    global $CFG, $OUTPUT, $pdo_database, $pdo_host, $pdo_port, $pdo_user, $pdo_pass, $info, $pdo_connection;

    if ( ! $pdo_host && ! $LAUNCH->user->instructor ) {
        echo("<p>You have not yet set up your database server for project <b>".htmlentities($project)."</b></p>\n");
        echo("<p>Make sure to run the setup process before attempting this assignment.</p>\n");
        return false;
    }

    if (! $pdo_host || strlen($pdo_host) < 1 ) {
        echo('<p style="color:red">It appears that your PostgreSQL environment is not yet set up or is not running.</p>'."\n");
    }

?>
<form name="myform" method="post" >
<p>
Host: <input type="text" name="pdo_host" value="<?= htmlentities($pdo_host) ?>" id="pdo_host" onchange="setPGAdminCookies();"><br/>
Port: <input type="text" name="pdo_port" value="<?= htmlentities($pdo_port) ?>" id="pdo_port" onchange="setPGAdminCookies();"><br/>
Database: <input type="text" name="pdo_database" value="<?= htmlentities($pdo_database) ?>" id="pdo_database" onchange="setPGAdminCookies();"><br/>
User: <input type="text" name="pdo_user" value="<?= htmlentities($pdo_user) ?>"><br/>
Password: <span id="pass" style="display:none"><input type="text" name="pdo_pass" id="pdo_pass" value="<?= htmlentities($pdo_pass) ?>"/></span> (<a href="#" onclick="$('#pass').toggle();return false;">hide/show</a> <a href="#" onclick="copyToClipboard(this, '<?= htmlentities($pdo_pass) ?>');return false;">copy</a>) <br/>
</pre>
<script>
function setPGAdminCookies() {
    var host = $("#pdo_host").val();
    var port = $("#pdo_port").val();
    var database = $("#pdo_database").val();
    console.log(port, host, database);
    var now = new Date();
    var time = now.getTime();
    var expireTime = time + 1000*36000;
    now.setTime(expireTime);

    document.cookie = 'pg4e_desc='+database+';expires='+now.toGMTString()+';path=/;SameSite=Secure';
    document.cookie = 'pg4e_port='+port+';expires='+now.toGMTString()+';path=/;SameSite=Secure';
    document.cookie = 'pg4e_host='+host+';expires='+now.toGMTString()+';path=/;SameSite=Secure';
}
</script>
<input type="submit" name="check" onclick="$('#submitspinner').show();return true;" value="Check Answer">
<img id="submitspinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display:none">
<input type="submit" name="default" value="Reset Values">
</form>
</p>
<p>
<?php if ( strlen($pdo_host) < 1 ) return; ?>
<?php if ( ! $terminalonly ) { ?>
You can do basic SQL commands using the
<a href="<?= $CFG->apphome ?>/phppgadmin" target="_blank">Online PostgreSQL Client</a> in your browser.
<?php } ?>
For batch loading or file uploads using the <b>\copy</b> command or to run Python programs,
you will need to access <b>python</b> or <b>psql</b> on the command line:</p>
<pre>
psql -h <?= htmlentities($pdo_host) ?> -p <?= htmlentities($pdo_port) ?> -U <?= htmlentities($pdo_user) ?> <?= htmlentities($pdo_database) ?>
<!--
Python Notebook:
%load_ext sql
%config SqlMagic.autocommit=False
%sql postgres://<?= htmlentities($pdo_user) ?>:replacewithsecret@<?= htmlentities($pdo_host) ?>:<?= htmlentities($pdo_port) ?>/<?= htmlentities($pdo_database) ?>
-->
</pre>
</p>
<?php
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
    $sql = "SELECT id, keystr, valstr FROM pg4e_meta";
    if ( ! pg4e_query_return_error($pg_PDO, $sql) ) {
        $_SESSION['error'] = "pg4e_debug exists but not pg4e_meta";
        header( 'Location: '.addSession('index.php') ) ;
        return false;
    }
    return true;
}

function pg4e_debug_note($pg_PDO, $note) {
    global $LAUNCH;
    $LAUNCH->result->setNote($note);
    if ( ! $pg_PDO ) return;
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

function pg4e_grade_send($LAUNCH, $pg_PDO, $oldgrade, $gradetosend, $dueDate) {
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

    if ( $pg_PDO ) {
        $pg_PDO->queryReturnError(
        "INSERT INTO pg4e_result (link_id, score, note, title, debug_log)
                    VALUES (:link_id, :score, :note, :title, :debug_log)",
            array(":link_id" => $LAUNCH->link->id, ":score" => $gradetosend,
               ":note" => $scorestr, ":title" => $LAUNCH->link->title,
               ":debug_log" => json_encode($debug_log)
             )
        );
    }
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

// TODO: Remove
function pg4e_user_es_load($LAUNCH) {

    // Transport the request to check past the redirect :)
    if ( U::get($_POST,'check') ) {
        $_SESSION['check'] = $_POST['check'];
    }
    // Returns true if redirected
    $retval = pg4e_user_es_post($LAUNCH);
    if ( $retval ) return false;

    // Restore check after redirect if one happens
    if ( U::get($_SESSION,'check') ) {
        $_POST['check'] = $_SESSION['check'];
        unset($_SESSION['check']);
    }

    // Set global values and cookies, etc.
    pg4e_user_es_data($LAUNCH);

    return true;
}

// Handle incoming POST request, redirecting if necessary
function pg4e_user_es_post($LAUNCH) {
    global $CFG;
    global $pg4e_request_result, $pg4e_request_url;
    global $es_host, $es_port, $es_prefix, $es_user, $es_pass, $info;

    if ( U::get($_POST,'default') ) {
        unset($_SESSION['es_host']);
        unset($_SESSION['es_prefix']);
        unset($_SESSION['es_port']);
        unset($_SESSION['es_user']);
        unset($_SESSION['es_pass']);
        setcookie("es_host", '', time()+31556926 ,'/');
        setcookie("es_prefix", '', time()+31556926 ,'/');
        setcookie("es_port", '', time()+31556926 ,'/');
        setcookie("es_user", '', time()+31556926 ,'/');
        setcookie("es_pass", '', time()+31556926 ,'/');
        // header( 'Location: '.addSession('index.php') ) ;
        redirect_200(addSession('index.php'));
        return true;
    }

    // If we have new values... copy them into SESSION
    $retval = false;
    foreach(array('es_host', 'es_prefix', 'es_port', 'es_user', 'es_pass') as $key) {
        $value = U::get($_POST, $key);
        if ( ! $value ) continue;
        $_SESSION[$key] = $value;
        $retval = true;
    }
    if ( $retval ) header( 'Location: '.addSession('index.php') ) ;
    return $retval;
}

// The instructor precedence with config is SESSION, COOKIE, CONFIG
// The student precedence with config is SESSION, CONFIG
// Without config, the precedence is SESSION, COOKIE
function pg4e_user_es_data($LAUNCH) {
    global $CFG;
    global $pg4e_request_result, $pg4e_request_url;
    global $es_host, $es_port, $es_prefix, $es_user, $es_pass, $info;

    $unique = getUnique($LAUNCH);
    $project = getDbName($unique);

    $cfg = getConfig();

    // Instructor / un-confgured defaults
    if ( true || $LAUNCH->user->instructor || ! $cfg ) {
        $es_host = U::get($_SESSION, 'es_host', U::get($_COOKIE, 'es_host'));
        $es_prefix = U::get($_SESSION, 'es_prefix', U::get($_COOKIE, 'es_prefix'));
        $es_port = U::get($_SESSION, 'es_port', U::get($_COOKIE, 'es_port'));
        $es_user = U::get($_SESSION, 'es_user', U::get($_COOKIE, 'es_user'));
        $es_pass = U::get($_SESSION, 'es_pass', U::get($_COOKIE, 'es_pass'));
    } else {  // Student && Config
        $es_host = U::get($_SESSION, 'es_host');
        $es_prefix = U::get($_SESSION, 'es_prefix');
        $es_port = U::get($_SESSION, 'es_port');
        $es_user = U::get($_SESSION, 'es_user');
        $es_pass = U::get($_SESSION, 'es_pass');
    }

    if ( ! $es_host && $cfg ) {
            $es_host = $cfg->es_host;
            $es_port = $cfg->es_port;
            $es_prefix = $cfg->es_prefix;
            $es_user = getEsUser($unique);
            $es_pass = es_makepw($es_user, '12345');
            $_SESSION['es_host'] = $es_host;
            $_SESSION['es_prefix'] = $es_prefix;
            $_SESSION['es_port'] = $es_port;
            $_SESSION['es_user'] = $es_user;
            $_SESSION['es_pass'] = $es_pass;
    }

    // Store in the database...
    $json = $LAUNCH->result->getJSON();
    $new = json_encode(array(
        'es_host' => $es_host,
        'es_prefix' => $es_prefix,
        'es_port' => $es_port,
        'es_user' => $es_user,
        'es_pass' => $es_pass,
    ));
    if ( $new != $json ) $LAUNCH->result->setJSON($new);

    // If we have a full set - store the cookies for good measure
    if ( $es_host &&  $es_port && $es_user && $es_pass ) {
        setcookie("es_host", $es_host, time()+31556926 ,'/');
        setcookie("es_prefix", $es_prefix, time()+31556926 ,'/');
        setcookie("es_port", $es_port, time()+31556926 ,'/');
        setcookie("es_user", $es_user, time()+31556926 ,'/');
        setcookie("es_pass", $es_pass, time()+31556926 ,'/');
    }

    return true;
}

/*
def makepw(user, secret):

    expire = getexpire(date)

    # user_2005
    index = user + '_' + str(expire)

    # user_2005_asecret
    base = index + '_' + secret

    m = hashlib.sha256()
    m.update(base.encode())
    sig = m.hexdigest()

    # 2005_7cce7423
    pw = str(expire) + '_' + sig[0:8]
    return pw
*/

// 2020-02-23 => 2005
function es_getexpire() {
    $future = date("Y/m/d", strtotime(" +3 months"));
    $retval = substr($future, 2, 2) . substr($future, 5, 2);
    return $retval;
}

// ('testing', '12345') => '2005_975c9677'
function es_makepw($user, $secret) {
    $expire = es_getexpire();
    $base = $user . '_' . $expire . '_' . $secret;
    $sig = hash('sha256', $base);
    $pw = $expire . '_' . substr($sig, 0, 8);
    return($pw);
}

function pg4e_user_es_form($LAUNCH) {
    global $OUTPUT, $es_host, $es_prefix, $es_port, $es_user, $es_pass, $info;

    $cfg = getConfig();

    if ( ! $es_host || strlen($es_host) < 1 ) {
       echo('<p style="color:red">It appears that your ElasticSearch environment is not yet set up or is not running.</p>'."\n");
    }
?>
<form name="myform" method="post" >
<p>
<?php if ( $LAUNCH->user->instructor || ! $cfg ) { ?>
Host: <input type="text" name="es_host" value="<?= htmlentities($es_host) ?>" id="es_host"><br/>
Prefix: <input type="text" name="es_prefix" value="<?= htmlentities($es_prefix) ?>" id="es_prefix"><br/>
Port: <input type="text" name="es_port" value="<?= htmlentities($es_port) ?>" id="es_port"><br/>
User: <input type="text" name="es_user" value="<?= htmlentities($es_user) ?>"><br/>
Password: <span id="pass" style="display:none"><input type="text" name="es_pass" id="es_pass" value="<?= htmlentities($es_pass) ?>"/></span> (<a href="#" onclick="$('#pass').toggle();return false;">hide/show</a> <a href="#" onclick="copyToClipboard(this, '<?= htmlentities($es_pass) ?>');return false;">copy</a>) <br/>
</pre>
<?php } else { ?>
<p>
<pre>
Host: <?= $es_host ?>

Prefix: <?= $es_prefix ?>

Port: <?= $es_port ?>

Account: <?= $es_user ?>

Password: <span id="pass" style="display:none"><?= $es_pass ?></span> <input type="hidden" name="es_pass" id="es_pass" value="<?= htmlentities($es_pass) ?>"/> (<a href="#" onclick="$('#pass').toggle();return false;">hide/show</a> <a href="#" onclick="copyToClipboard(this, '<?= htmlentities($es_pass) ?>');return false;">copy</a>)
</pre>
</p>
<?php } ?>
<input type="submit" name="check" onclick="$('#submitspinner').show();return true;" value="Check Answer">
<img id="submitspinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display:none">
<input type="submit" name="default" value="Reset Values">
</form>
</p>
</p>
<?php
}

function pg4e_insert_meta($PDO, $keystr, $valstr) {
    $PDO->queryDie(
        "INSERT INTO pg4e_meta (keystr, valstr) VALUES (:keystr, :valstr)
                ON CONFLICT (keystr) DO UPDATE SET valstr=:valstr, updated_at=now();",
        array(":keystr" => $keystr, ":valstr" => $valstr)
    );
    $PDO->queryDie("COMMIT");
}

function pg4e_setup_meta($LAUNCH, $PDO ) {
    global $CFG;
    $sql = "
CREATE TABLE IF NOT EXISTS pg4e_meta (
  id SERIAL,
  keystr VARCHAR(128) UNIQUE,
  valstr VARCHAR(4096),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP,
  PRIMARY KEY(id)
);
";
    $stmt = $PDO->queryReturnError($sql);
    if ( ! $stmt->success ) {
        error_log("Sql Failure:".$stmt->errorImplode." ".$sql);
        return false;
    }

    // Put some stuff in there.
    $date_utc = new \DateTime("now", new \DateTimeZone("UTC"));
    $date_utc = $date_utc->format('Y-m-d');
    $valstr = md5($date_utc.'::'.$LAUNCH->context->key.'::'.md5($CFG->dbpass).'::42::'.
                ($LAUNCH->user->id*42).'::'.($LAUNCH->context->id*42));
    pg4e_insert_meta($PDO, "user_id", $LAUNCH->user->id);
    pg4e_insert_meta($PDO, "context_id", $LAUNCH->context->id);
    pg4e_insert_meta($PDO, "key", $LAUNCH->context->key);
    pg4e_insert_meta($PDO, "access", $date_utc);
    pg4e_insert_meta($PDO, "code", $valstr);

}

function get_connection($connection, $user, $pass) {
    try {
        $retval = new PDOX($connection, $user, $pass,
            array(
                PDO::ATTR_TIMEOUT => 5, // in seconds
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            )
        );
        return $retval;
    } catch(Exception $e) {
        $error = $e->getMessage();
        error_log("Connection failure user=$user $connection $error");
        return null;
    }
}

