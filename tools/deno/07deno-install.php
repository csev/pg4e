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
$code = dn_maketoken($code, "42");

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

a[target="_blank"]::after {
  content: '↗️'; /* Or use a character like '↗' */
  margin-left: 0.25rem; /* Add spacing */
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
<h1>Deno Install <span id="passfail"></span></h1>
<h2>Option 1: Use pre-installed Deno Server</h2>
<p>
Installing your own instance of Deno is <b>optional</b> for this assignment.   If you don't have a Github account 
and/or don't want to share your github login information with Deno Deploy, you can simply use a shared
Deno instance at <a href="https://deno.pg4e.com/" target="_blank">https://deno.pg4e.com</a>.
Skip down to the "Using kvadmin.py" section of these instructions:
<p>
<a href="https://github.com/csev/deno-kv-admin/blob/main/README.md#using-kvadminpy" target="_blank">
https://github.com/csev/deno-kv-admin/blob/main/README.md</a>
</p>
Use the following values in your <b>hidden.py</b> configuration file as part of the installation.
<pre>
def denokv():
    return { "token" : "<?php echo($code); ?>",
             "url": "https://deno.pg4e.com"}
</pre>
<p>
Follow the instructions to test <b>kvadmin.py</b> to make sure you are ready for the remaining assignments.
Then to get credit for this assignment enter <a href="https://deno.pg4e.com/" target="_blank">https://deno.pg4e.com</a> in 
the URL field below and press "Check".
</p>
<p>
<h2>Option 2: Install your Own Deno Server</h2>
To install your own Deno instance complete this assignment, you will use your Github credentials create a free 
<a href="https://dash.deno.com/" target="_blank">Deno Deploy</a> account.
Once you have your Deploy account, follow theese instructions to install both a Deno server and a Python
client:
</p>
<p>
<a href="https://github.com/csev/deno-kv-admin/blob/main/README.md" target="_blank">
https://github.com/csev/deno-kv-admin/blob/main/README.md</a>
</p>
<p>
Make sure to set your <b>token</b> value to be <b><?php echo($code); ?></b> in the 
<b>checkToken()</b> function in your Deno server.
</p>
<p>
Then when you install <b>kvadmin.py</b> edit the <b>denokv()</b> method in <b>hidden.py</b> to be:
<pre>
def denokv():
    return { "token" : "<?php echo($code); ?>",
             "url": "https://comfortable-starling-12.deno.dev"}
</pre>
Please replace "comfortable-starling-12" with the server name you were assigned by Deno.
</p>
<h2>Checking your URL</h2>
<p>
Once the software has been installed and configured, you can check the assignment here.
<ul>
<li>If you created your own Deno server, enter its URL with no trailing slash below.</li>
<li>If you did not create your own Deno server, enter
<a href="https://deno.pg4e.com" target="_blank">https://deno.pg4e.com</a> below.</li>
</ul>
</p>
<p>
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

$dumpurl = $url . "/dump";
$listgood = $url .= "/kv/list/trees?token=".$code;
$listbad = $url .= "/kv/list/trees?token=badleroy";
?>

<div id="grader-output">
<p>
<b>(Step 1/3)</b> Checking server connectivity at:
<pre>
<?php
$json = denoGetJSON($dumpurl);
if ( ! is_object($json) ) {
    echo("</div>\n");
    return;
}
echo(json_encode($json, JSON_PRETTY_PRINT));
?>
</pre>
<p>
<b>(Step 2/3) </b>Running a KV list operation with the correct token (should return an empty JSON list)
<p>
<pre>
<?php
$json = denoGetJSON($listgood);
if ( ! is_object($json) ) {
    echo("<p>A request with a good token is supposed to return valid JSON</p>\n");
    echo("<p style=\"color: red;\"><b>Assignment not passed</b></p>");
    echo('<script>document.getElementById("passfail").textContent = "(not passed)";</script>'."\n");
    echo("</div>\n");
    return;
}
echo(json_encode($json, JSON_PRETTY_PRINT));
?>
</pre>
<p>
<b>(Step 3/3) </b>Running a KV list operation with incorrect token (should fail with Missing or invalid token)
</p>
<pre>
<?php
$json = denoGetJSON($listbad);
if ( is_object($json) ) {
    echo("<p>A request with bad token is *not* supposed to return valid JSON</p>\n");
    echo("<p style=\"color: red;\"><b>Assignment not passed</b></p>");
    echo("<pre>\n");echo(json_encode($json, JSON_PRETTY_PRINT));echo("\n</pre>\n");
    echo('<script>document.getElementById("passfail").textContent = "(not passed)";</script>'."\n");
    echo("</div>\n");
    return;
}
?>
</pre>
<p><b style=\"color: green;\">Congratulations, you have passed this autograder!</b></p>
<script>document.getElementById("passfail").textContent = '(passed)';</script>
<?php
    $gradetosend = 1.0;
    $debug_log = array();
    $retval = LTIX::gradeSend($gradetosend, false, $debug_log);
    echo("<?--\n");
    echo(htmlentities(Output::safe_var_dump($debug_log)));
    echo("-->\n");
?>
</div>
