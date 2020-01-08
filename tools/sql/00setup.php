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

// Note grade is sent from load_info.php
$spinner = '<img src="'.$OUTPUT->getSpinnerUrl().'">';
echo("<p>You get a grade for this assignment once your environment has been created.</p>\n");
echo("<p>Postgres superuser details for project: ".htmlentities($dbname)."</p>\n");
echo("<pre>\n");
echo('Server: <span id="server">'.$spinner."</span>\n");
echo('Port: <span id="port">'.$spinner."</span>\n");
echo('User: <span id="user">'.$spinner."</span>\n");
echo("Password: ");
echo('<span id="pass" style="display:none">'.$spinner.'</span> (<a href="#" onclick="$(\'#pass\').toggle();return false;">hide/show password</a> ');
echo('<a href="#" onclick="copyToClipboard(this, $(\'#pass\').text());return false;">copy</a>');
echo(')'."\n");
echo('Status: <span id="status">'.$spinner.'</span>');
?>
</pre>
<p id="access_delay">It usually takes about a two minutes to create your database the first time...</p>
<div id="access_instructions" style="display:none;">
<p>You can do basic SQL commands using the
<a href="<?= $CFG->apphome ?>/phppgadmin" target="_blank">Online PostgreSQL Client</a> in your browser.
For batch loading or to run Python programs, you will need to access to <b>psql</b> on the command line, using this command to connect:</p>
<pre>
<?php
$tunnel = $LAUNCH->link->settingsGet('tunnel');
if ( $tunnel == 'yes' ) {
?>
You may need to set up SSH port forwarding to connect to the database through a
login server that you can access.  In one window, run

ssh -4 -L <span id="localport1"><?= $spinner ?></span>:<span id="server2"><?= $spinner ?></span>:<span id="port3"><?= $spinner ?></span> account@login-server

In a second window, run:

psql -h 127.0.0.1 -p <span id="localport2"><?= $spinner ?></span> -U <span id="user2"><?= $spinner ?></span>

If your commmand line is not behind a fire wall you can skip port forwarding and type:

<?php }  ?>
psql -h <span id="server3"><?= $spinner ?></span> -p <span id="port5"><?= $spinner ?></span> -U <span id="user3"><?= $spinner ?></span>
</pre>
<p>If you just created your database for the very first time, it might take an extra minute
or so after you have an IP address
before you can actually connect.  Please give it another try if it does not respond instantly.
</p>
<p>To prepare for the upcoming assignments, use the above credentials to create
a user <b><?= htmlentities($dbuser) ?></b> and then create a
database named <b>pg4e</b> and give the user access to that database.  This database and role
will be used to complete the rest of your assignments in this class.  The only use of the your superuser
credentials is to create the database and role.
</p>
<p>
If you are using the web client (phppgadmin), make sure to run these two commands separately
or you will get a transaction error.
</p>
<pre>
-- You must <a href="#" onclick="$('#dbpass2').toggle();$('#reveal').hide();return false;">click here to show the password</a> on the CREATE USER command before you copy the command

CREATE USER <?= htmlentities($dbuser) ?> WITH PASSWORD '<span id="dbpass2" style="display:none"><?= htmlentities($dbpass) ?></span>'; <span id="reveal">-- Reveal the password before copying</span>
CREATE DATABASE pg4e WITH OWNER '<?= htmlentities($dbuser) ?>';
</pre>
If you make a mistake and want to reset or recreate the user or database, use the following SQL commands
while logged in with your superuser credentials:
<pre>
DROP DATABASE pg4e;
DROP USER <?= htmlentities($dbuser) ?>;
</pre>
You need to drop them in this order because the user cannot be dropped until the database
no longer exists.
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
  $("#port").html('');
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

              var now = new Date();
              var time = now.getTime();
              var expireTime = time + 1000*36000;
              now.setTime(expireTime);
              document.cookie = 'pg4e_desc=<?= $dbname ?>;expires='+now.toGMTString()+';path=/;SameSite=Secure';
              document.cookie = 'pg4e_host='+retval.ip+';expires='+now.toGMTString()+';path=/;SameSite=Secure';
              document.cookie = 'pg4e_port='+retval.port+';expires='+now.toGMTString()+';path=/;SameSite=Secure';
              console.log(document.cookie);

              $("#server").html(retval.ip);
              $("#server2").html(retval.ip);
              $("#server3").html(retval.ip);
              $("#server4").html(retval.ip);
              $("#port").html(retval.port);
              $("#port2").html(retval.port);
              $("#port3").html(retval.port);
              $("#port4").html(retval.port);
              $("#port5").html(retval.port);
              $("#localport1").html(retval.port < 10000 ? Number(retval.port) + 10000 : retval.port);
              $("#localport2").html(retval.port < 10000 ? Number(retval.port) + 10000 : retval.port);
              $("#access_instructions").show();
              $("#access_delay").hide();
              $("#client1").hide();
              $("#client2").show();
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
