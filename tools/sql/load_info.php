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
$dbname = getDbName($unique);

if ( $LAUNCH->user->instructor && U::get($_GET, 'dbname') ) {
    $dbname = U::get($_GET, 'dbname');
}

$es_cfg = getESConfig();
$es_retval = pg4e_request($dbname, 'info/es', $es_cfg);
$es_info = false;
if ( is_object($es_retval) ) {
  $es_info = pg4e_extract_es_info($es_retval);
}

$cfg = getUMSIConfig();
$retval = pg4e_request($dbname, 'info/pg', $cfg);
$info = false;

if ( is_object($retval) ) {
  $info = pg4e_extract_info($retval);
  if ( $info && isset($info->ip) && strlen($info->ip) > 1 ) {
    $r = LTIX::gradeSend(1.0, false, $debug_log);
  }
  if ( $es_info ) $info->es = $es_info;
}



if ( ! $info )  {
  $info = new \stdClass();
  $info->error = $retval;
  $info->gmdate = gmdate("M d Y H:i:s");
  $info->detail = $pg4e_request_result;
  $info->json = json_decode($pg4e_request_result);
  if ( $es_info ) $info->es = $es_info;
}

if ( ! is_object($retval) ) $info->retval = $retval;
if ( ! is_object($es_retval) ) $info->es_retval = $es_retval;

echo(json_encode($info, JSON_PRETTY_PRINT));

$unique = getUnique($LAUNCH);
$info->dbname = getDbName($unique);
$info->dbuser = getDbUser($unique);
$info->dbpass = getDbPass($unique);

$json = $LAUNCH->result->getJSON();
$new = json_encode($info);
if ( $new != $json ) $LAUNCH->result->setJSON($new);


