<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Util\Mersenne_Twister;
use \Tsugi\Grades\GradeUtil;

require_once "deno_util.php";

// Compute the stuff for the output
$code = getUnique($LAUNCH);
$code = substr($code, 0, 8);

$config = getCourseSettings();

$oldgrade = $RESULT->grade;

$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : "";
$url = rtrim(trim($url), '/');

if ( U::get($_POST,'check') ) {

    // TODO: Actually check
 
    $gradetosend = 1.0;
    // pg4e_grade_send($LAUNCH, $pg_PDO, $oldgrade, $gradetosend, $dueDate);

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

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
Please enter the URL of your server with no trailing slash:
<form id="checkForm" onsubmit="showSpinner()">
<input type="text" style="width: 65%;" name="url" value="<?= htmlentities($url); ?>">
<button type="submit" class="check-button" id="checkButton">
    <span class="spinner-container">
        <div class="spinner"></div>
    </span>
    <span class="button-text">Check</span>
</button>
</form>
<pre>
<?php
if ( strlen($url) > 0 ) {
$endpoint = $url .= "/dump";

$header = '';
$returnval = Net::doGet($endpoint,$header);

echo($endpoint."\n");
echo(htmlentities($returnval));
echo("\n");

echo(json_encode(
    json_decode($returnval),
    JSON_PRETTY_PRINT
));

// Net::doBody($membershipsurl, "POST", $body,$header) 
}
?>
</pre>

<script>
function showSpinner() {
    const form = document.getElementById('checkForm');
    const button = document.getElementById('checkButton');
    
    // Add loading class to form
    form.classList.add('loading');
    
    // Disable the button
    button.disabled = true;
    
    // The form will submit normally after this
}
</script>
