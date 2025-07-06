<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Util\Mersenne_Twister;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;
use \Tsugi\Grades\GradeUtil;

require_once "deno_util.php";

// Compute the stuff for the output
$code = getUnique($LAUNCH);
$code = substr($code, 0, 8);

$config = getCourseSettings();

$oldgrade = $RESULT->grade;

$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : "";
$url = rtrim(trim($url), '/');

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<style>
a, a:link, a:visited, a:hover
{
    text-decoration: underline;
}

.spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spinner-container {
    display: none;
}

.loading .spinner-container {
    display: inline-block;
}

.loading .check-button {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
<h1>Deno Install</h1>
<p>
In this assignment, you will create a free 
<a href="https://dash.deno.com/" target="_blank">Deno Deploy</a> account and
install the server side of the <a href="https://www.pg4e.com/code/kvadmin.py" target="_blank">kvadmin.py</a>
command line KV client in Python.
Once you have your Deploy account, follow the instructions at:
</p>
<p>
<a href="https://github.com/csev/deno-kv-admin/blob/main/README.md" target="_blank">
https://github.com/csev/deno-kv-admin/blob/main/README.md</a>
</p>
<p>
Make sure to set your <b>token</b> value to be <b><?php echo($code); ?></b> in both these locations:
<ul>
<li>On the Deno Playground server <b>checkToken()</b> function</li>
<li>For the <b>kvadmin.py</b> client in the <b>denokv()</b> method in <b>hidden.py</b></li>
</ul>
</p>
<p>
Please enter the URL of your server with no trailing slash.
You can test the autograder with the reference implementation by entering
<a href="https://kv-admin-api.pg4e.com/" target="_blank">https://kv-admin-api.pg4e.com</a>
<!-- https://kv-admin-api.pg4e.com/ -->
<form id="checkForm" onsubmit="showSpinner()">
<input type="text" style="width: 65%;" name="url" value="<?= htmlentities($url); ?>">
<button type="submit" class="check-button" id="checkButton">
    <span class="spinner-container">
        <div class="spinner"></div>
    </span>
    <span class="button-text">Check</span>
</button>
</form>
<hr/>
<script>
function showSpinner() {
    const form = document.getElementById('checkForm');
    const button = document.getElementById('checkButton');
    
    // Add loading class to form
    form.classList.add('loading');
    
    // Disable the button
    button.disabled = true;
    
    // The form will submit normally after this
    const myDiv = document.getElementById('grader-output');
    console.log(myDiv);
    if ( myDiv != null ) myDiv.style.display = 'none';
}
</script>

<?php 
if ( strlen($url) < 1 ) return; 

if ( strpos($url,'.pg4e.com') ) {
    $code = '42';
}

$dumpurl = $url . "/dump";
?>

<div id="grader-output">
<p>
<b>(Step 1)</b> Checking server connectivity at:
<a href="<?= htmlentities($dumpurl) ?>" target="_blank"><?= htmlentities($dumpurl) ?></a>
<p>
<pre>
<?php

$header = '';
$returnval = Net::doGet($dumpurl,$header);
$http_status = Net::getLastHttpResponse();
$error = Net::getLastCurlError();
if ( !is_string($returnval) || strlen($returnval) < 1 ) {
    echo("No response received.\n");
    echo("Status: $http_status\n");
    echo("Error: $error\n");
    echo("</pre>\n");
?>
<p>
<b>Notre:</b> Make sure to launch the dump url in the browser before running the autograder.
Sometimes when a free Deno Deploy instance has not been active for a while, it
can take 30 seconds or more for it to cold start.   You should access the URL in a browser
and refresh it until you get a corrct response befomre coming back and re-running
the autograder.
</p>
<?php
    echo("</div>\n");
    return;
}

$json = json_decode($returnval);
if ( ! is_object($json)) {
    echo("JSON Error: " . json_last_error_msg() . "\n");
    echo("<!--\n".htmlentities($returnval)."\n-->\n");
    echo("</div>\n");
    echo("</pre>\n");
    return;
} else {
    echo(json_encode($json, JSON_PRETTY_PRINT));
}

// Net::doBody($membershipsurl, "POST", $body,$header) 
?>
</pre>
<?php
$listgood = $url .= "/kv/list/trees?token=".$code;
$listbad = $url .= "/kv/list/trees?token=badleroy";
?>
<p>
<b>(Step 2) </b>Running a KV list operation with the correct token (should return an empty JSON list)
<a href="<?= htmlentities($listgood) ?>" target="_blank"><?= htmlentities($listgood) ?></a>
<p>
<pre>
<?php
$json = denoGetJSON($listgood);
if ( ! is_object($json) ) return;
echo(json_encode($json, JSON_PRETTY_PRINT));
?>
</pre>
<p>
<b>(Step 3) </b>Running a KV list operation with incorrect token (should fail with Missing or invalid token)
</p>
<pre>
<?php
$json = denoGetJSON($listbad);
if ( is_object($json) ) {
    echo("A bad token is not supposed to return valid JSON\n");
    echo(json_encode($json, JSON_PRETTY_PRINT));
    return;
}
?>
</pre>
<b>Congratulations, you have passed this autograder!</b>
<?php
    $gradetosend = 1.0;
    $debug_log = array();
    $retval = LTIX::gradeSend($gradetosend, false, $debug_log);
    echo("<?--\n");
    echo(htmlentities(Output::safe_var_dump($debug_log)));
    echo("-->\n");
?>
</div>
