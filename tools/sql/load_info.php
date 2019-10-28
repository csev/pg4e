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

$retval = pg4e_request($dbname);
$info = false;
if ( is_object($retval) ) {
  $info = pg4e_extract_info($retval);
} else {
  $info = new \stdClass();
  $info->error = $retval;
  $info->detail = $pg4e_request_result;
}

echo(json_encode($info, JSON_PRETTY_PRINT));


