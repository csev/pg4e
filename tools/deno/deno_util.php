<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\Mersenne_Twister;
use \Tsugi\Core\LTIX;

function denoGetJSON($url) {
    echo('Retrieving <a href="'.htmlentities($url).'" target="_blank">'.htmlentities($url)."</a>\n");
    $header = '';
    $returnval = Net::doGet($url,$header);
    $http_status = Net::getLastHttpResponse();
    $error = Net::getLastCurlError();
    if ( !is_string($returnval) || strlen($returnval) < 1 ) {
       echo("No response received.\n");
        echo("Status: $http_status\n");
        echo("Error: $error\n");
        echo("</pre>\n");
?>
<p>
<b>Note:</b> Make sure to launch the dump url in the browser and verify correct operation
before re-running the autograder.
Sometimes when a free Deno Deploy instance has not been active for a while, it
can take 30 seconds or more for it to cold start.   You should access the URL in a browser
and refresh it until you get a correct response before coming back and re-running
the autograder.
</p>
<?php
        return false;
    }

    $json = json_decode($returnval);
    if ( ! is_object($json)) {
        echo("JSON Error: " . json_last_error_msg() . "\n");
        echo(substr(htmlentities($returnval), 0, 100)."\n");
        echo("</pre>\n");
        return false;
    } else {
        return $json;
        echo(json_encode($json, JSON_PRETTY_PRINT));
    }
}

// Unique to user + course
function getCode($LAUNCH) {
    return $LAUNCH->user->id*42+$LAUNCH->context->id;
}

function getUnique($LAUNCH) {
    return md5($LAUNCH->user->key.'::'.$LAUNCH->context->key.
        '::'.$LAUNCH->user->id.'::'.$LAUNCH->context->id);
}

function getCourseSettings() {
    global $TSUGI_LAUNCH;

    $settings = $TSUGI_LAUNCH->context->settingsGetAll();
    return $settings;
}

/*
def makepw(user, secret):

    expire = getexpire(date)

    # user_2005
    index = user + '_' + str(expire)

    # user_2005_asecret
    base = index + '_' + secret

    m = hashlib.sha256()
    m.update(base.encode())
    sig = m.hexdigest()

    # 2005_7cce7423
    pw = str(expire) + '_' + sig[0:8]
    return pw
*/

// 2020-02-23 => 2005
function dn_getexpire() {
    $future = date("Y/m/d", strtotime(" +3 months"));
    $retval = substr($future, 2, 2) . substr($future, 5, 2);
    return $retval;
}

// ('testing', '12345') => '2005_975c9677'
function dn_makepw($user, $secret) {
    $expire = dn_getexpire();
    $base = $user . '_' . $expire . '_' . $secret;
    $sig = hash('sha256', $base);
    $pw = $expire . '_' . substr($sig, 0, 8);
    return($pw);
}

/* JavaScript
// Date three months from now
// 2025-10-16T03:15:18.917Z
let d = new Date(new Date().setMonth(new Date().getMonth() + 3));
console.log(d, typeof d);
let ds = d.toISOString();
console.log(ds, typeof ds);
// 2510 (year / month)
let expire = ds.substring(2,4) + ds.substring(5,7);
console.log(expire);

let user = "abc123";
let secret = "12345";

let plain = user + '_' + expire + '_' + secret;
console.log(plain);
*/
function getBooks () {
    $books =  array(
        '{"author": "Bill", "title": "Hamlet", "isbn": "42"}',
        '{"author": "Katie", "title": "Wizards", "isbn": "6848"}',
        '{"author": "Chuck", "title": "PY4E", "isbn": "8513"}',
        '{"author": "Kristen", "title": "PI", "isbn": "8162"}',
        '{"author": "James", "title": "Wisdom", "isbn": "3857"}',
        '{"author": "Barb", "title": "Mind", "isbn": "8110"}',
        '{"author": "Vittore", "title": "Tutti", "isbn": "1730"}',
        '{"author": "Chuck", "title": "Net", "isbn": "8151"}',
        '{"author": "Fernando", "title": "Redes", "isbn": "5236"}',
        '{"author": "Clayton", "title": "Innovators", "isbn": "6206"}',
        '{"author": "Katy", "title": "Wizards", "isbn": "6848"}',
        '{"author": "Ann", "title": "Unlocking", "isbn": "1339"}',
        '{"author": "Tim", "title": "Weaving", "isbn": "6251"}',
    );
    // Make sure all are parseable
    foreach ( $books as $book ) {
        $bookjson = json_decode($book);
        $isbn = $bookjson->isbn;
        $author = $bookjson->author;
        $title = $bookjson->title;
    }
    return $books;
}
function denoCSSandJS() {
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
}
