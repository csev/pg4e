<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$MAX_UPLOAD_FILE_SIZE = 1024*1024;

require_once "sql_util.php";

$dbname = getDbName($unique);
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
   $try_create = true;
   $retval = pg4e_request($dbname, 'create');
   $create_request = $pg4e_request_result;
   $retval = pg4e_request($dbname);
   $info2_request = $pg4e_request_result;
   $info = false;
   if ( is_object($retval) ) {
     $info = pg4e_extract_info($retval);
   }
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
if ( $try_create == 404 ) {
    $spinner = '<img src="'.$OUTPUT->getSpinnerUrl().'">';
    echo("<p>Details for ".htmlentities($dbname).":</p>\n");
    echo("<pre>\n");
    echo('Server: <span id="server">'.$spinner."</span>\n");
    echo('User: <span id="user">'.$spinner."</span>\n");
    echo("Password: ");
    echo('<span id="pass" style="display:none">'.$spinner.'</span> (<a href="#" onclick="$(\'#pass\').toggle();return false;">hide/show</a>)'."\n");
    echo('Status: <span id="status">'.$spinner.'</span>');
    echo("</pre>\n");
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

$OUTPUT->footerStart();

$ajax_url = "load_info.php";
if ( $LAUNCH->user->instructor ) $ajax_url .= '?dbname=' . urlencode($dbname);
$ajax_url = addSession($ajax_url);
?>
<script type="text/javascript">
load_tries = 0;
function clearFields() {
  $("#user").html('');
  $("#pass").html('');
  $("#server").html('');
}

function updateMsg() {
  window.console && console.log('Requesting JSON'); 
  $.getJSON('<?= $ajax_url ?>', function(retval){
      load_tries = load_tries + 1;
      window.console && console.log(load_tries);
      window.console && console.log(retval);
      if ( retval && retval.error ) {
	if ( retval.error == 500 ) {
            $("#status").html("Internal server error");
            clearFields();
            return;
        }
	if ( retval.error == 401 ) {
            $("#status").html("The back end API is not authorized (internal error)");
            clearFields();
            return;
	}
      }
      if ( retval.error && retval.error == 404 ) {
          $("#status").html(load_tries + ' attempt(s), latest error='+retval.error);
          if ( load_tries < 10 ) setTimeout('updateMsg()', 10000);
          return;
      }
      if ( retval.error && retval.error != 200 ) {
          $("#status").html(load_tries + ' attempt(s), latest error='+retval.error);
          clearFields();
          return;
      }
      $("#user").html(retval.user);
      $("#pass").html(retval.password);

      if ( retval.ip && retval.ip.length > 0 ) {
          $("#status").html("Environment created");
          if ( retval.ip ) $("#server").html(retval.ip);
      } else { 
          $("#status").html("Waiting on environment creation ("+load_tries+")");
          setTimeout('updateMsg()', 5000);
      }
  });
}

// Make sure JSON requests are not cached
$(document).ready(function() {
  $.ajaxSetup({ cache: false });
  updateMsg();
});
</script>
<?php
$OUTPUT->footerEnd();
// global $FOOTER_DONE;
$FOOTER_DONE = true;
