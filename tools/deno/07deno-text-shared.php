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
$codeint = getCode($LAUNCH);
$textlines = explode("\n", file_get_contents('01-intro.txt'));
$textline = $textlines[$codeint % count($textlines)];
$textkey = "/py4e/chapter01_" . $codeint;

$config = getCourseSettings();

$oldgrade = $RESULT->grade;

$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : "";
$url = rtrim(trim($url), '/');
$sampleurl = "https://deno.pg4e.com";
if ( strlen($url) > 0 ) $sampleurl = $url;
 
// Wake up the sleeping Deno if it is sleeping
$command = 'nohup curl https://deno.pg4e.com/dump > /tmp/deno-ping 2>&1 &';
exec($command);

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
<h1>Configuring Deno KVAdmin <span id="passfail"></span></h1>
<p>
If you have not already done so you need to download the following files into a folder on your 
computer:
<ul>
<li><a href="https://www.pg4e.com/code/kvadmin.py" target="_blank">https://www.pg4e.com/code/kvadmin.py</a></li>
<li><a href="https://www.pg4e.com/code/hidden-dist.py" target="_blank">https://www.pg4e.com/code/hidden-dist.py</a> and copy it
to <b>hidden.py</b> unless you already have <b>hidden.py</b> from previous assignments.
</ul>
</p>
<p>
Edit the <b>hidden.py</b>, find the <b>denokv()</b> function and edit it as follows:
<pre>
def denokv():
    return { "token" : "<?php echo($code); ?>",
             "url": "<?php echo($sampleurl); ?>"}
</pre>
<p>
Then run <b>kvadmin.py</b> as follows:
<pre>
python kvadmin.py
Verifying connection to <?= $sampleurl ?>


Enter command: <b>set <?= $textkey ?></b>

Enter json (finish with a blank line):
<b>{"text": "<?= htmlentities($textline) ?>"}</b>

<?= $sampleurl ?>/kv/set/pg4e/<?= $codeint ?>?token=<?= $code ?>

{
  "ok": true,
  "versionstamp": "010000000842de400000"
}

Enter command: quit
</pre>
</p>
Then check your result here in this autograder.
<form id="checkForm" onsubmit="showSpinner()">
<input type="hidden" name="url" value="https://deno.pg4e.com">
<button type="submit" name="check" class="check-button" id="checkButton">
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
$geturl = $url . "/kv/get".$textkey."?token=".$code;
$getbad = $url . "/kv/get".$textkey."?token=badleroy";
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
<b>(Step 2/3) </b>Running a KV get operation to retrieve your text data
<p>
<pre>
<?php
$json = denoGetJSON($geturl);
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
<?php
$retrievedtext = (isset($json->value) && isset($json->value->text)) ? $json->value->text : null;
var_dump($retrievedtext);
if ( ! is_string($retrievedtext) || strlen($retrievedtext) < 1 ) {
    echo("<p>Could not find the retrieved text under value->text</p>\n");
    echo("<p>Remember that you can overwrite a Deno KV document by using set again.</p>\n");
    echo("<p style=\"color: red;\"><b>Assignment not passed</b></p>");
    echo('<script>document.getElementById("passfail").textContent = "(not passed)";</script>'."\n");
    echo("</div>\n");
    return;

} else if ( $retrievedtext == $textline ) {
    echo("<p><b>Retrieved text matched expected text</b></p\n");
} else {
    echo("<p>Mismatch of value->text in retrieved data</p>\n");
    echo("<p>Expected: ".htmlentities($textline)."</p>\n");
    echo("<p>Found: ".htmlentities($retrievedtext)."</p>\n");
    echo("<p>Remember that you can overwrite a Deno KV document by using set again.</p>\n");
    echo("<p style=\"color: red;\"><b>Assignment not passed</b></p>");
    echo('<script>document.getElementById("passfail").textContent = "(not passed)";</script>'."\n");
    echo("</div>\n");
    return;
}

?>
<p>
<b>(Step 3/3) </b>Running a KV get operation with incorrect token (should fail with Missing or invalid token)
</p>
<pre>
<?php
$json = denoGetJSON($getbad);
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
<?php
?>
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
