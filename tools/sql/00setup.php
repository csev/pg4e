<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

$MAX_UPLOAD_FILE_SIZE = 1024*1024;

require_once "sql_util.php";

if ( ! pg4e_unlock($LAUNCH) ) {
    $FOOTER_DONE = true;
    return false;
}

$dbname = getDbName($unique);
$dbuser = getDbUser($unique);
$dbpass = getDbPass($unique);

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

$info = false;
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
<a href="info.php" class="btn btn-normal">Test Harness</a>
<a href="load_info.php?dbname=<?= $dbname ?>" target="_blank" class="btn btn-normal">JSON</a>
</p>
<?php } ?>
<p>
<?php
if ( is_string($retval) ) {
    echo("<p>Error retrieving environment: ".htmlentities($dbname)."<br/>".htmlentities($retval)."</p>\n");
    return;
}

$spinner = '<img src="'.$OUTPUT->getSpinnerUrl().'">';
echo("<p>You get a grade for this assignment once your environment has been created.</p>\n");
echo("<p>Postgres superuser details for project: ".htmlentities($dbname)."</p>\n");
echo("<pre>\n");
echo('Server: <span id="server">'.$spinner."</span>\n");
echo('User: <span id="user">'.$spinner."</span>\n");
echo("Password: ");
echo('<span id="pass" style="display:none">'.$spinner.'</span> (<a href="#" onclick="$(\'#pass\').toggle();return false;">hide/show</a> ');
echo('<a href="#" onclick="copyToClipboard(this, $(\'#pass\').text());return false;">copy</a>');
echo(')'."\n");
echo('Status: <span id="status">'.$spinner.'</span>');
echo("</pre>\n");
echo("<p>To access this in a command line / terminal use:</p>\n");
echo("<pre>\n");
$tunnel = $LAUNCH->link->settingsGet('tunnel');
if ( $tunnel == 'yes' ) {
echo('Make sure your port 5432 is forwarded to <span id="server2">'.$spinner.'</span> and then:');
echo("\n\n");
echo('psql -h 127.0.0.1 -U <span id="user2">'.$spinner."</span>\n");
} else {
echo('psql -h <span id="server2">'.$spinner.'</span> -U <span id="user2">'.$spinner."</span>\n");
}
echo("</pre>\n");
echo("<p id=\"access_delay\">It usually takes about a minute to create your database the first time...</p>\n");
echo('<div id="access_instructions" style="display:none;">'."\n");
?>
<p>Before going on to the next step, use your super user credentials to create a role
with the following details:
<pre>
User: <?= htmlentities($dbuser) ?>

Password: <span id="dbpass" style="display:none"><?= htmlentities($dbpass) ?></span> (<a href="#" onclick="$('#dbpass').toggle();return false;">hide/show</a> <a href="#" onclick="copyToClipboard(this, $('#dbpass').text());return false;">copy</a>)
</pre>
And create a database named <b>pg4e</b> and give the new role access to that database.  This database and role
will be used to complete and grade the rest of your assignments in this class.
</p>
<pre>
postgres=# CREATE USER <?= htmlentities($dbuser) ?> WITH PASSWORD 'replacewithsecret';
CREATE ROLE
postgres=# CREATE DATABASE pg4e WITH OWNER '<?= htmlentities($dbuser) ?>';
CREATE DATABASE
postgres=# \q
</pre>
If you make a mistake and want to reset or recreate the user or database, use:
<pre>
DROP DATABASE pg4e;
DROP USER <?= htmlentities($dbuser) ?>;
</pre>
</div>
<?php
$OUTPUT->footerStart();

$ajax_url = "load_info.php";
if ( $LAUNCH->user->instructor ) $ajax_url .= '?dbname=' . urlencode($dbname);
$ajax_url = addSession($ajax_url);
?>
<script type="text/javascript">
load_tries = 0;
function clearFields() {
  $("#user").html('');
  $("#user2").html('(tbd)');
  $("#user3").html('(tbd)');
  $("#pass").html('');
  $("#server").html('');
  $("#server").html('(tbd)');
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
      $("#user2").html(retval.user);
      $("#user3").html(retval.user);
      $("#pass").html(retval.password);

      if ( retval.ip && retval.ip.length > 0 ) {
          $("#status").html("Environment created");
          if ( retval.ip ) {
              $("#server").html(retval.ip);
              $("#server2").html(retval.ip);
              $("#server3").html(retval.ip);
              $("#access_instructions").show();
              $("#access_delay").hide();
          }
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
$FOOTER_DONE = true;
