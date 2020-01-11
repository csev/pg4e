<?php

require_once "../config.php";
require_once "sql_util.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;
$unique = getUnique($LAUNCH);

if ( ! $LAUNCH->user->instructor ) die('Must be instructor');

if ( ! pg4e_unlock_check($LAUNCH) ) die('not unlocked');

$dbname = getDbName($unique);
$dbname = U::get($_REQUEST, 'dbname', $dbname);

$url = addSession("info.php?dbname=".urlencode($dbname));

if ( U::get($_POST,'create') ) {
    $retval = pg4e_request($dbname, 'create');
    $_SESSION['operation'] = 'create';
    $_SESSION['retval'] = $retval;
    $_SESSION['pg4e_request_result'] = $pg4e_request_result;
    $_SESSION['pg4e_request_url'] = $pg4e_request_url;
    header('Location: '. $url);
    return;
} else if ( U::get($_POST,'delete') ) {
    $retval = pg4e_request($dbname, 'delete');
    $_SESSION['operation'] = 'delete';
    $_SESSION['pg4e_request_result'] = $pg4e_request_result;
    $_SESSION['pg4e_request_url'] = $pg4e_request_url;
    $_SESSION['retval'] = $retval;
    header('Location: '. $url);
    return;
} else if ( U::get($_POST,'info') ) {
    $retval = pg4e_request($dbname, 'info/pg');
    $_SESSION['operation'] = 'delete';
    $_SESSION['pg4e_request_result'] = $pg4e_request_result;
    $_SESSION['pg4e_request_url'] = $pg4e_request_url;
    $_SESSION['retval'] = $retval;
    header('Location: '. $url);
    return;
}

$retval = U::get($_SESSION,'retval', null);
unset($_SESSION['retval']);
$info = false;
if ( is_object($retval) ) {
   $info = pg4e_extract_info($retval);
}
$pg4e_request_result = U::get($_SESSION,'pg4e_request_result', null);
$pg4e_request_url = U::get($_SESSION,'pg4e_request_url', null);
unset($_SESSION['pg4e_request_result']);
unset($_SESSION['pg4e_request_url']);
$operation = U::get($_SESSION,'operation', null);
unset($_SESSION['operation']);

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
?>
<h1>Postgres Info</h1>
<?php if ( $LAUNCH->user->instructor ) { ?>
<form method="post">
Value to check:
<input type="text" size="30" name="dbname" value="<?= htmlentities($dbname) ?>"><br/>
<input type="submit" name="create" value="Create">
<input type="submit" name="info" value="Retrieve Info">
<input type="submit" name="delete" value="Delete">
<a href="index.php?dbname=<?= urlencode($dbname) ?>" class="btn btn-normal">Cancel</a>
</form>
<?php } ?>
<p>
<?php
// p41d45acd-postgres-public created
$createString = $dbname.'-postgres-public created';
// p41d45acd-postgres-public" deleted
$deleteString = $dbname.'-postgres-public" deleted';
if ( $retval === null ) {

} else if ( is_string($retval) && strpos($retval, $createString) > 0 ) {
    echo("<p>Environment ".htmlentities($dbname)." being created - use info to check status.</p>\n");
} else if ( is_string($retval) && strpos($retval, $deleteString) > 0 ) {
    echo("<p>Environment ".htmlentities($dbname)." being deleted.</p>\n");
} else if ( is_int($retval) && $retval == 200 ) {
    if ( $operation == 'create' ) {
    	echo("<p>Environment ".htmlentities($dbname)." being created - use info to check status.</p>\n");
    } else if ( $operation == 'delete' ) {
    	echo("<p>Environment ".htmlentities($dbname)." being deleted - use info to check status.</p>\n");
    }
} else if ( is_int($retval) && $retval == 404 ) {
    echo("<p>Environment ".htmlentities($dbname)." not found.</p>\n");
} else if ( is_string($retval) ) {
    echo("<p>Error retrieving environment: ".htmlentities($dbname)."<br/>".htmlentities($retval)."</p>\n");
} else if ( is_object($info) ) {
    echo("<p>Details for ".htmlentities($dbname).":</p>\n");
    echo("<pre>\n");
    echo("Server: ".(strlen($info->ip) > 0 ? $info->ip : "not set")."\n");
    echo("User: ".$info->user."\n");
    echo("Port: ".$info->port."\n");
    echo("Password: ");
    echo('<span id="pass" style="display:none">'.htmlentities($info->password).'</span> (<a href="#" onclick="$(\'#pass\').toggle();return false;">hide/show</a>)'."\n");
    echo("psql -h ".htmlentities($info->ip)." -p ".htmlentities($info->port)." -U ".htmlentities($info->user)."\n");
    echo("</pre>\n");
}
if ( $pg4e_request_result != null ) {
    echo("<p>\n");
    echo(htmlentities($pg4e_request_url));
    echo("</p>\n");
    echo("<hr/>\n<pre>\n");
    echo("Returned data:\n");
    echo(htmlentities($pg4e_request_result));
    echo("\n</pre>\n");
}
$OUTPUT->footerStart();
$OUTPUT->footerEnd();
