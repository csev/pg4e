<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$MAX_UPLOAD_FILE_SIZE = 1024*1024;

require_once "sql_util.php";

$dbname = "pg4e_".$unique;
$dbname = "pg4e42";
if ( $LAUNCH->user->instructor && U::get($_GET, 'dbname') ) {
    $dbname = U::get($_GET, 'dbname');
}
$info1_request = false;
$create_request = false;
$info2_request = false;

$retval = pg4e_request($dbname);
$info1_request = $pg4e_request_result;
$info = false;
if ( is_object($retval) ) {
  $info = pg4e_extract_info($retval);
}

$try_create = false;
if ( ! $info ) {
   echo("<pre>\n");
   echo("Creating....\n");
   $try_create = true;
   $retval = pg4e_request($dbname, 'create');
   $create_request = $pg4e_request_result;
   echo("Create complete....\n");
   $retval = pg4e_request($dbname);
   $info2_request = $pg4e_request_result;
   $info = false;
   if ( is_object($retval) ) {
     $info = pg4e_extract_info($retval);
   }
   echo("\n</pre>\n");
}
?>
<h1>Postgres Setup</h1>
<?php if ( $LAUNCH->user->instructor ) { ?>
<p>
<a href="info.php" class="btn btn-normal">Debug</a>
<a href="load_info.php?dbname=<?= $dbname ?>" target="_blank" class="btn btn-normal">JSON</a>
</p>
<?php } ?>
<p>
<?php
if ( is_int($retval) && $retval == 404 ) {
    echo("<p>Environment ".htmlentities($dbname)." not found.</p>\n");
} else if ( is_string($retval) ) {
    echo("<p>Error retrieving environment: ".htmlentities($dbname)."<br/>".htmlentities($retval)."</p>\n");
} else if ( is_object($info) ) {
    echo("<p>Details for ".htmlentities($dbname).":</p>\n");
    echo("<pre>\n");
    echo("Server: ".$info->ip."\n");
    echo("User: ".$info->user."\n");
    echo("Password: ");
    echo('<span id="pass" style="display:none">'.htmlentities($info->password).'</span> (<a href="#" onclick="$(\'#pass\').toggle();return false;">hide/show</a>)'."\n");
    echo("psql -h ".htmlentities($info->ip)." -U ".htmlentities($info->user)."\n");
    echo("</pre>\n");
}
if (  $LAUNCH->user->instructor ) {
    echo("<hr/>\n<pre>\n");
    echo("First info request:\n");
    var_dump($info1_request);
    echo("<hr/>Create request:\n");
    var_dump($create_request);
    echo("<hr/>Second info request:\n");
    var_dump($info2_request);
    echo("</pre>\n");
}
$OUTPUT->footerStart();
$OUTPUT->footerEnd();
