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

$cfg = getUMSIConfig();
$retval = pg4e_request($dbname, 'info/pg', $cfg);
$info = false;
if ( is_object($retval) ) {
  $info = pg4e_extract_info($retval);
  if ( $info && isset($info->ip) && strlen($info->ip) > 1 ) {
    $retval = LTIX::gradeSend(1.0, false, $debug_log);
  }
} else {
  $info = new \stdClass();
  $info->error = $retval;
  $info->gmdate = gmdate("M d Y H:i:s");
  $info->detail = $pg4e_request_result;
  $info->json = json_decode($pg4e_request_result);
}

echo(json_encode($info, JSON_PRETTY_PRINT));

$unique = getUnique($LAUNCH);
$info->dbname = getDbName($unique);
$info->dbuser = getDbUser($unique);
$info->dbpass = getDbPass($unique);

$json = $LAUNCH->result->getJSON();
$new = json_encode($info);
if ( $new != $json ) $LAUNCH->result->setJSON($new);


