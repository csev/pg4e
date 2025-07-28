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

$books = getBooks();
$book = $books[$codeint % count($books)];
$bookjson = json_decode($book);
$isbn = $bookjson->isbn;
$author = $bookjson->author;
$title = $bookjson->title;
$textkey = "/py4e/chapter01_" . $codeint;

$config = getCourseSettings();

$oldgrade = $RESULT->grade;

$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : "";
$url = rtrim(trim($url), '/');
$sampleurl = "https://comfortable-starling-12.deno.dev";

if ( strlen($url) > 0 ) $sampleurl = $url;

// Wake up the sleeping Deno if it is sleeping
$command = 'nohup curl https://deno.pg4e.com/dump > /tmp/deno-ping 2>&1 &';
exec($command);

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}

denoCSSandJS(); // Print out the CSS
?>
<h1>Deno Book Data Model <span id="passfail"></span></h1>
<p>
In this assignment you will insert a book into a shared 
Deno Deploy instance
using your installed 
<a href="https://www.pg4e.com/code/kvadmin.py" target="_blank">kvadmin.py</a>
web services client from the previous assignment.
</p>
<p>
Your <b>hidden.py</b> should have the following in its <b>denokv()</b> method:
<pre>
def denokv():
    return { "token" : "<?php echo($code); ?>",
             "url": "<?php echo($sampleurl); ?>"}
</pre>
</p>
<p>
The lecture proposes a data model for a primary logical key based on <b>isbn</b>
for the book JSON and secondary indexes for <b>title</b> and <b>author</b>.
Create the keys and values according to the data model proposed for the following book
in your Deno KV instance:
<pre>
<?= htmlentities($book) ?>
</pre>
</p>
Then check your result here in this autograder.
<form id="checkForm" onsubmit="showSpinner()">
<input type="hidden" name="url" value="https://deno.pg4e.com">
<button type="submit" class="check-button" id="checkButton">
    <span class="spinner-container">
        <div class="spinner"></div>
    </span>
    <span class="button-text">Check</span>
</button>
</form>
<hr/>
<?php 
if ( strlen($url) < 1 ) return; 

$dumpurl = $url . "/dump";
$geturl = $url . "/kv/get".$textkey."?token=".$code;
$getisbn = $url . "/kv/get/books/isbn/".$isbn."?token=".$code;
$getauthor = $url . "/kv/get/books/author/".$author."/".$isbn."?token=".$code;
$gettitle = $url . "/kv/get/books/title/".$title."/".$isbn."?token=".$code;
$getbad = $url . "/kv/get".$textkey."?token=badleroy";
?>

<div id="grader-output">
<p>
<b>(Step 1/5)</b> Checking server connectivity at:
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
<b>(Step 2/5) </b>Running a KV get operation to retrieve your isbn record
<p>
<pre>
<?php
$json = denoGetJSON($getisbn);
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
$value = isset($json->value) ? $json->value : null;
if ( ! is_object($value) ) {
    echo("<p>Could not find the retrieved value from the returned JSON.</p>\n");
    echo("<p>Remember that you can overwrite a Deno KV document by using set again.</p>\n");
    echo("<p style=\"color: red;\"><b>Assignment not passed</b></p>");
    echo('<script>document.getElementById("passfail").textContent = "(not passed)";</script>'."\n");
    echo("</div>\n");
    return;
}

if ( ! isset($value->author) || ! isset($value->title) || !isset($value->isbn) ) {
    echo("<p>Missing author, isbn, or author in the returned JSON.</p>\n");
    echo("<p>Remember that you can overwrite a Deno KV document by using set again.</p>\n");
    echo("<p style=\"color: red;\"><b>Assignment not passed</b></p>");
    echo('<script>document.getElementById("passfail").textContent = "(not passed)";</script>'."\n");
    echo("</div>\n");
    return;
}

if ( $value->author != $author || $value->title != $title || $value->isbn != $isbn) {
    echo("<p>Mismatch of the author, isbn, or author in the returned JSON.</p>\n");
    echo("<p>Remember that you can overwrite a Deno KV document by using set again.</p>\n");
    echo("<p style=\"color: red;\"><b>Assignment not passed</b></p>");
    echo('<script>document.getElementById("passfail").textContent = "(not passed)";</script>'."\n");
    echo("</div>\n");
    return;
}

echo("<p><b>Retrieved value matched expected value</b></p\n");
?>
<p>
<b>(Step 3/5) </b>Running a KV get operation to retrieve your author record
<p>
<pre>
<?php
$json = denoGetJSON($getauthor);
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
$value = isset($json->value) ? $json->value : null;
if ( ! is_object($value) ) {
    echo("<p>Could not find the retrieved value from the returned JSON.</p>\n");
    echo("<p>Remember that you can overwrite a Deno KV document by using set again.</p>\n");
    echo("<p style=\"color: red;\"><b>Assignment not passed</b></p>");
    echo('<script>document.getElementById("passfail").textContent = "(not passed)";</script>'."\n");
    echo("</div>\n");
    return;
}

if ( !empty(get_object_vars($value)) ) {
    echo("<p>Using the secondary key pattern, we store an empty object under the secondary key.</p>\n");
    echo("<p>Remember that you can overwrite a Deno KV document by using set again.</p>\n");
    echo("<p style=\"color: red;\"><b>Assignment not passed</b></p>");
    echo('<script>document.getElementById("passfail").textContent = "(not passed)";</script>'."\n");
    echo("</div>\n");
    return;
}

echo("<p><b>Secondary key entry for author has an empty object (correct)</b></p\n");
?>
<p>
<b>(Step 4/5) </b>Running a KV get operation to retrieve your title record</b>
<p>
<pre>
<?php
$json = denoGetJSON($gettitle);
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
$value = isset($json->value) ? $json->value : null;
if ( ! is_object($value) ) {
    echo("<p>Could not find the retrieved value from the returned JSON.</p>\n");
    echo("<p>Remember that you can overwrite a Deno KV document by using set again.</p>\n");
    echo("<p style=\"color: red;\"><b>Assignment not passed</b></p>");
    echo('<script>document.getElementById("passfail").textContent = "(not passed)";</script>'."\n");
    echo("</div>\n");
    return;
}

if ( !empty(get_object_vars($value)) ) {
    echo("<p>Using the secondary key pattern, we store an empty object under the secondary key.</p>\n");
    echo("<p>Remember that you can overwrite a Deno KV document by using set again.</p>\n");
    echo("<p style=\"color: red;\"><b>Assignment not passed</b></p>");
    echo('<script>document.getElementById("passfail").textContent = "(not passed)";</script>'."\n");
    echo("</div>\n");
    return;
}

echo("<p><b>Secondary key entry for title has an empty object (correct)</b></p\n");
?>
<p>
<b>(Step 5/5) </b>Running a KV get operation with incorrect token (should fail with Missing or invalid token)
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
