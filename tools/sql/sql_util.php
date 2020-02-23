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

function getEsUser($unique) {
    return "pg4e_".substr($unique,12,7);
}

function getUMSIConfig() {
    global $CFG, $TSUGI_LAUNCH;

    $settings = $TSUGI_LAUNCH->context->settingsGetAll();
    $db_source = U::get($settings, 'db_source');
    if ( $db_source == 'elephant' ) return false;

    $retval = new \stdclass();
    $umsi_url = U::get($settings, 'umsi_url');
    $umsi_key = U::get($settings, 'umsi_key');
    $umsi_password = U::get($settings, 'umsi_password');
    if ( strlen($umsi_url) > 0 && strlen($umsi_key) > 0 || strlen($umsi_password) > 0 ) {
        $retval->pg4e_api_url = $umsi_url;
        $retval->pg4e_api_key = $umsi_key;
        $retval->pg4e_api_password = $umsi_password;
        return $retval;
    }

    $umsi_url = isset($CFG->pg4e_api_url) ? $CFG->pg4e_api_url : false;
    $umsi_key = isset($CFG->pg4e_api_key) ? $CFG->pg4e_api_key : false;
    $umsi_password = isset($CFG->pg4e_api_password) ? $CFG->pg4e_api_password : false;
    if ( strlen($umsi_url) > 0 && strlen($umsi_key) > 0 || strlen($umsi_password) > 0 ) {
        $retval->pg4e_api_url = $umsi_url;
        $retval->pg4e_api_key = $umsi_key;
        $retval->pg4e_api_password = $umsi_password;
        return $retval;
    }
    return false;
}

function getCourseSettings() {
    global $TSUGI_LAUNCH;

    $settings = $TSUGI_LAUNCH->context->settingsGetAll();
    return $settings;
}

function getESConfig() {
    global $CFG;

    $settings = getCourseSettings();
    $es_source = U::get($settings, 'es_source');
    if ( strlen($es_source) < 1 || $es_source == 'none') return false;

    $retval = new \stdclass();
    $retval->es_source = $es_source;

    $retval->pg4e_api_url = U::get($settings, 'um_es_url');
    $retval->pg4e_api_key = U::get($settings, 'um_es_key');
    $retval->pg4e_api_password = U::get($settings, 'um_es_password');

    $retval->es_host = U::get($settings, 'es_host');
    $retval->es_prefix = U::get($settings, 'es_prefix');
    $retval->es_port = U::get($settings, 'es_port');
    $retval->es_password = U::get($settings, 'es_password');
    return $retval;
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
function pg4e_request($dbname, $path='info/pg', $cfg) {
    global $pg4e_request_result, $pg4e_request_url, $pg4e_request_status;

    if ( ! $cfg ) {
        return "UMSI API is not configured.";
    }

    $pg4e_request_result = false;
    $pg4e_request_url = false;
    $pg4e_request_url = $cfg->pg4e_api_url.'/'.$path.'/'.$dbname;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pg4e_request_url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $cfg->pg4e_api_key.':'.$cfg->pg4e_api_password);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    $pg4e_request_result = curl_exec($ch);
    if($pg4e_request_result === false)
    {
        return 'Curl error: ' . curl_error($ch);
    }
    $pg4e_request_status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ( $pg4e_request_status != 200 ) return $pg4e_request_status;

    // It seems as though create success returns '"" '
    if ( $pg4e_request_status == 200 && trim($pg4e_request_result) == '""' ) return 200;

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
         $retval->user = isset($info->auth->data->POSTGRES_USER) ? base64_decode($info->auth->data->POSTGRES_USER) : null;
         $retval->password = isset($info->auth->data->POSTGRES_PASSWORD) ? base64_decode($info->auth->data->POSTGRES_PASSWORD) : null;
         $retval->ip = $info->ing->status->loadBalancer->ingress[0]->ip ?? null;
         $retval->port = $info->svc->metadata->labels->port ?? null;
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

// TODO: Remove
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
<head><meta http-equiv="refresh" content=1;url="<?=$url?>"></head>
<body><a href="<?=$url?>">...</a></body>
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
        // header( 'Location: '.addSession('index.php') ) ;
        redirect_200(addSession('index.php'));
        return true;
    }

    // Cannot set these from post unless we are unconfigured or instructor
    $cfg = getUMSIConfig();
    if ( $cfg && ! $LAUNCH->user->instructor ) return false;

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

    $cfg = getUMSIConfig();

    $unique = getUnique($LAUNCH);
    $project = getDbName($unique);

    $default_database = '';
    $default_user = '';
    $default_pass = '';
    if ( $cfg ) {
        $default_database = 'pg4e';
        $default_user = getDbUser($unique);
        $default_pass = getDbPass($unique);
    }

    // Instructor / un-confgured defaults
    if ( $LAUNCH->user->instructor || ! $cfg ) {
        $pdo_database = U::get($_SESSION, 'pdo_database', U::get($_COOKIE, 'pdo_database', $default_database));
        $pdo_host = U::get($_SESSION, 'pdo_host', U::get($_COOKIE, 'pdo_host'));
        $pdo_port = U::get($_SESSION, 'pdo_port', U::get($_COOKIE, 'pdo_port'), '5432');
        $pdo_user = U::get($_SESSION, 'pdo_user', U::get($_COOKIE, 'pdo_user', $default_user));
        $pdo_pass = U::get($_SESSION, 'pdo_pass', U::get($_COOKIE, 'pdo_pass', $default_pass));
    } else {  // Student && Config
        $pdo_database = U::get($_SESSION, 'pdo_database', $default_database);
        $pdo_host = U::get($_SESSION, 'pdo_host');
        $pdo_port = U::get($_SESSION, 'pdo_port', '5432');
        $pdo_user = U::get($_SESSION, 'pdo_user', $default_user);
        $pdo_pass = U::get($_SESSION, 'pdo_pass', $default_pass);
    }

    // If we don't yet have a host and we are configured, grab one from the server
    if ( ! $pdo_host && $cfg ) {
        $retval = pg4e_request($project, 'info/pg', $cfg);
        if ( is_int($retval) && $retval == 500 ) {
            echo("<pre>\n");
            echo("Your PostgreSQL server is not yet setup.\n\n");
            return;
        }
        if ( is_int($retval) && $retval >= 300 ) {
            echo("<pre>\n");
            echo("Internal provisioning error. Please send the text below to csev@umich.edu\n\n");
            echo("HTTP Code: ".$retval."\n\n");
            echo("Requesting: ".$pg4e_request_url."\n\n");
            echo("Result:\n");
            echo(htmlentities(wordwrap($pg4e_request_result)));
            die();
        }
        $info = false;
        if ( is_object($retval) ) {
            $info = pg4e_extract_info($retval);
            if ( isset($info->ip) ) $pdo_host = $info->ip;
            if ( isset($info->port) ) $pdo_port = $info->port;
            if ( strlen($pdo_port) < 1 ) $pdo_port = '5432';
            // Save later API retrievals
            $_SESSION['pdo_host'] = $pdo_host;
            $_SESSION['pdo_port'] = $pdo_port;
        }
    }

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
    global $OUTPUT, $pdo_database, $pdo_host, $pdo_port, $pdo_user, $pdo_pass, $info, $pdo_connection;

    if ( ! $pdo_host && ! $LAUNCH->user->instructor ) {
        echo("<p>You have not yet set up your database server for project <b>".htmlentities($project)."</b></p>\n");
        echo("<p>Make sure to run the setup process before attempting this assignment.</p>\n");
        return false;
    }

    $cfg = getUMSIConfig();

    $tunnel = $LAUNCH->link->settingsGet('tunnel');
    if ( ! $cfg ) {
    ?>
<p>Please enter your PostgreSQL credentials below. You need to have an Internet-accessible
database server so we can grade your assignments..
There is company called
<a href="https://www.elephantsql.com/plans.html" target="_blank">ElephantSQL</a> that provides
a no-charge very small instance of PostgreSQL
(Tiny Turtle) that should work for the purposes of these assignments.  Not that on ElephantSQL
the database name and user name are the same.
</p>
<?php

    } else if (! $pdo_host || strlen($pdo_host) < 1 ) {
        echo('<p style="color:red">It appears that your PostgreSQL environment is not yet set up or is not running.</p>'."\n");
    }
    if ( strlen($pdo_port) < 1 ) $pdo_port = "5432";
?>
<form name="myform" method="post" >
<p>
<?php if ( $LAUNCH->user->instructor || ! $cfg ) { ?>
Host: <input type="text" name="pdo_host" value="<?= htmlentities($pdo_host) ?>" id="pdo_host" onchange="setPGAdminCookies();"><br/>
Port: <input type="text" name="pdo_port" value="<?= htmlentities($pdo_port) ?>" id="pdo_port" onchange="setPGAdminCookies();"><br/>
Database: <input type="text" name="pdo_database" value="<?= htmlentities($pdo_database) ?>" id="pdo_database" onchange="setPGAdminCookies();"><br/>
User: <input type="text" name="pdo_user" value="<?= htmlentities($pdo_user) ?>"><br/>
Password: <span id="pass" style="display:none"><input type="text" name="pdo_pass" id="pdo_pass" value="<?= htmlentities($pdo_pass) ?>"/></span> (<a href="#" onclick="$('#pass').toggle();return false;">hide/show</a> <a href="#" onclick="copyToClipboard(this, '<?= htmlentities($pdo_pass) ?>');return false;">copy</a>) <br/>
</pre>
<script>
function setPGAdminCookies() {
    global $CFG;
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
<?php } else { ?>
<p>
<pre>
Host: <?= $pdo_host ?>

Port: <?= $pdo_port ?>

Database: <?= $pdo_database ?>

Account: <?= $pdo_user ?>

Password: <span id="pass" style="display:none"><?= $pdo_pass ?></span> <input type="hidden" name="pdo_pass" id="pdo_pass" value="<?= htmlentities($pdo_pass) ?>"/> (<a href="#" onclick="$('#pass').toggle();return false;">hide/show</a> <a href="#" onclick="copyToClipboard(this, '<?= htmlentities($pdo_pass) ?>');return false;">copy</a>)
</pre>
</p>
<?php } ?>
<input type="submit" name="check" onclick="$('#submitspinner').show();return true;" value="Check Answer">
<img id="submitspinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display:none">
<?php if ( $LAUNCH->user->instructor || ! $cfg ) { ?>
<input type="submit" name="default" value="Default Values">
<?php } ?>
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
<?php if ( $tunnel == 'yes' ) {
    $localport = $pdo_port;
    if ( $pdo_port < 10000 ) $localport = $pdo_port + 10000;
?>
You may need to set up SSH port forwarding through a server that you have access to
to connect to the database.  In one window, run

ssh -4 -L <?= htmlentities($localport) ?>:<?= htmlentities($pdo_host) ?>:<?= htmlentities($pdo_port) ?> your-account@your-login-server

In a second window, run:

psql -h 127.0.0.1 -p <?= htmlentities($localport) ?> -U <?= htmlentities($pdo_user) ?> <?= htmlentities($pdo_database) ?>

<!--
Python Notebook:
%load_ext sql
%config SqlMagic.autocommit=False
%sql postgres://<?= htmlentities($pdo_user) ?>:replacewithsecret@127.0.0.1:<?= htmlentities($pdo_port) ?>/<?= htmlentities($pdo_database) ?>
-->
If you have psql running somewhere that is not behind a firewall, use the command:
<?php } ?>
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
    $sql = "SELECT id, keystr, valstr FROM pg4e_meta";
    if ( ! pg4e_query_return_error($pg_PDO, $sql) ) {
        $_SESSION['error'] = "pg4e_debug exists, please create pg4e_meta";
        header( 'Location: '.addSession('index.php') ) ;
        return false;
    }

    $stmt = $pg_PDO->queryReturnError($sql);
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
    if ( is_elastic7() ) {
        $scorestr = "Your answer is correct - score saved.";
    }
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

    // Cannot set these from post unless we are unconfigured or instructor
    $cfg = getUMSIConfig();
    if ( $cfg && ! $LAUNCH->user->instructor ) return false;

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

    $cfg = getESConfig();

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
        if ( is_elastic7() ) {
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
        } else {
            $retval = pg4e_request($project, 'info/es', $cfg);
            if ( is_int($retval) && $retval == 500 ) {
                echo("<pre>\n");
                echo("Your Elastic Search server is not yet setup.\n\n");
                return;
            }
            if ( is_int($retval) && $retval >= 300 ) {
                echo("<pre>\n");
                echo("Internal provisioning error. Please send the text below to csev@umich.edu\n\n");
                echo("HTTP Code: ".$retval."\n\n");
                echo("Requesting: ".$pg4e_request_url."\n\n");
                echo("Result:\n");
                echo(htmlentities(wordwrap($pg4e_request_result)));
                die();
            }
            $info = false;
            if ( is_object($retval) ) {
                $info = pg4e_extract_es_info($retval);
                if ( isset($info->ip) ) $es_host = $info->ip;
                if ( isset($info->prefix) ) $es_prefix = $info->prefix;
                if ( isset($info->port) ) $es_port = $info->port;
                if ( isset($info->user) ) $es_user = $info->user;
                if ( isset($info->password) ) $es_pass = $info->password;
                $_SESSION['es_host'] = $es_host;
                $_SESSION['es_prefix'] = $es_prefix;
                $_SESSION['es_port'] = $es_port;
                $_SESSION['es_user'] = $es_user;
                $_SESSION['es_pass'] = $es_pass;
            }
        }
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

function pg4e_extract_es_info($info) {
    $user = false;
    $password = false;
    $ip = false;
    try {
        $retval = new \stdClass();
         $retval->user = base64_decode($info->auth->data->ADMIN_USERNAME);
         $retval->password = base64_decode($info->auth->data->ADMIN_PASSWORD);
         $retval->ip = $info->ing->status->loadBalancer->ingress[0]->ip ?? null;
         $retval->port = $info->svc->metadata->labels->port ?? null;
        return $retval;
    } catch(Exception $e) {
        return null;
    }
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

function is_elastic7() {
    $the_version = $_GET['es_version'] ?? $_COOKIE['es_version'] ?? 'elastic6';
    return $the_version == 'elastic7';
}

function pg4e_user_es_form($LAUNCH) {
    global $OUTPUT, $es_host, $es_prefix, $es_port, $es_user, $es_pass, $info;

// TODO: Fix
// echo("<p style=\"color: red;\">The elastic search assignments are currently under construction, Dr. Chuck will announce when they are again available...</p>\n");
// return;

    $cfg = getESConfig();

    $tunnel = $LAUNCH->link->settingsGet('tunnel');
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
