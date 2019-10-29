<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

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

function pg4e_unlock_check($LAUNCH) {
    global $CFG;
    if ( $LAUNCH->context->key != '12345' ) return true;
    $unlock_code = md5(getUnique($LAUNCH) . $CFG->pg4e_unlock) ;
    if ( U::get($_COOKIE, 'unlock_code') == $unlock_code ) return true;
    return false;
}

function pg4e_unlock($LAUNCH) {
    global $CFG, $OUTPUT;
    if ( pg4e_unlock_check($LAUNCH) ) return true;

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

    $pdo_database = 'pg4e';
    $pdo_host = false;
    $pdo_user = false;
    $pdo_pass = false;

    $unique = getUnique($LAUNCH);
    $project = getDbName($unique);
    $pdo_user = getDbUser($unique);
    $pdo_pass = getDbPass($unique);

    $pdo_host = U::get($_SESSION,'pdo_host');

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
        $pdo_host = U::get($_POST, 'pdo_host', $pdo_host);
        $pdo_database = U::get($_POST, 'pdo_database', $pdo_database);
        $pdo_user = U::get($_POST, 'pdo_user', $pdo_user);
        $pdo_pass = U::get($_POST, 'pdo_pass', $pdo_pass);
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
