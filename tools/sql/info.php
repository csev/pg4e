<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$MAX_UPLOAD_FILE_SIZE = 1024*1024;

require_once "sql_util.php";

$dbname = "pg4e_".$unique;
$retval = pg4e_info($dbname);
$info = false;
if ( is_object($retval) ) {
  $info = pg4e_extract_info($retval);
}
?>
<h1>Postgres Info</h1>
<p>
<?= $unique ?>
Results of the info query.
<pre>
<?= var_dump($info); ?>
<?= var_dump($retval); ?>
</pre>
