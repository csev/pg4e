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

// 2508-02-23 => 2005
function dn_getexpire() {
    $future = date("Y/m/d", strtotime(" +3 months"));
    $retval = substr($future, 2, 2) . substr($future, 5, 2);
    return $retval;
}

// ('testing', '12345') => '2508_testing:975c9677'
function dn_maketoken($user, $secret) {
    $expire = dn_getexpire();
    $base = $expire . '_' . substr($user, 0, 6) . ':' . $secret;
    $sig = md5($base);
    $pw = $expire . '_' . substr($user, 0, 6) . ':' . substr($sig, 0, 6);
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

// https://stackoverflow.com/a/33486055/1994792
var MD5 = function(d){var r = M(V(Y(X(d),8*d.length)));return r.toLowerCase()};function M(d){for(var _,m="0123456789ABCDEF",f="",r=0;r<d.length;r++)_=d.charCodeAt(r),f+=m.charAt(_>>>4&15)+m.charAt(15&_);return f}function X(d){for(var _=Array(d.length>>2),m=0;m<_.length;m++)_[m]=0;for(m=0;m<8*d.length;m+=8)_[m>>5]|=(255&d.charCodeAt(m/8))<<m%32;return _}function V(d){for(var _="",m=0;m<32*d.length;m+=8)_+=String.fromCharCode(d[m>>5]>>>m%32&255);return _}function Y(d,_){d[_>>5]|=128<<_%32,d[14+(_+64>>>9<<4)]=_;for(var m=1732584193,f=-271733879,r=-1732584194,i=271733878,n=0;n<d.length;n+=16){var h=m,t=f,g=r,e=i;f=md5_ii(f=md5_ii(f=md5_ii(f=md5_ii(f=md5_hh(f=md5_hh(f=md5_hh(f=md5_hh(f=md5_gg(f=md5_gg(f=md5_gg(f=md5_gg(f=md5_ff(f=md5_ff(f=md5_ff(f=md5_ff(f,r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+0],7,-680876936),f,r,d[n+1],12,-389564586),m,f,d[n+2],17,606105819),i,m,d[n+3],22,-1044525330),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+4],7,-176418897),f,r,d[n+5],12,1200080426),m,f,d[n+6],17,-1473231341),i,m,d[n+7],22,-45705983),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+8],7,1770035416),f,r,d[n+9],12,-1958414417),m,f,d[n+10],17,-42063),i,m,d[n+11],22,-1990404162),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+12],7,1804603682),f,r,d[n+13],12,-40341101),m,f,d[n+14],17,-1502002290),i,m,d[n+15],22,1236535329),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+1],5,-165796510),f,r,d[n+6],9,-1069501632),m,f,d[n+11],14,643717713),i,m,d[n+0],20,-373897302),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+5],5,-701558691),f,r,d[n+10],9,38016083),m,f,d[n+15],14,-660478335),i,m,d[n+4],20,-405537848),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+9],5,568446438),f,r,d[n+14],9,-1019803690),m,f,d[n+3],14,-187363961),i,m,d[n+8],20,1163531501),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+13],5,-1444681467),f,r,d[n+2],9,-51403784),m,f,d[n+7],14,1735328473),i,m,d[n+12],20,-1926607734),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+5],4,-378558),f,r,d[n+8],11,-2022574463),m,f,d[n+11],16,1839030562),i,m,d[n+14],23,-35309556),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+1],4,-1530992060),f,r,d[n+4],11,1272893353),m,f,d[n+7],16,-155497632),i,m,d[n+10],23,-1094730640),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+13],4,681279174),f,r,d[n+0],11,-358537222),m,f,d[n+3],16,-722521979),i,m,d[n+6],23,76029189),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+9],4,-640364487),f,r,d[n+12],11,-421815835),m,f,d[n+15],16,530742520),i,m,d[n+2],23,-995338651),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+0],6,-198630844),f,r,d[n+7],10,1126891415),m,f,d[n+14],15,-1416354905),i,m,d[n+5],21,-57434055),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+12],6,1700485571),f,r,d[n+3],10,-1894986606),m,f,d[n+10],15,-1051523),i,m,d[n+1],21,-2054922799),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+8],6,1873313359),f,r,d[n+15],10,-30611744),m,f,d[n+6],15,-1560198380),i,m,d[n+13],21,1309151649),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+4],6,-145523070),f,r,d[n+11],10,-1120210379),m,f,d[n+2],15,718787259),i,m,d[n+9],21,-343485551),m=safe_add(m,h),f=safe_add(f,t),r=safe_add(r,g),i=safe_add(i,e)}return Array(m,f,r,i)}function md5_cmn(d,_,m,f,r,i){return safe_add(bit_rol(safe_add(safe_add(_,d),safe_add(f,i)),r),m)}function md5_ff(d,_,m,f,r,i,n){return md5_cmn(_&m|~_&f,d,_,r,i,n)}function md5_gg(d,_,m,f,r,i,n){return md5_cmn(_&f|m&~f,d,_,r,i,n)}function md5_hh(d,_,m,f,r,i,n){return md5_cmn(_^m^f,d,_,r,i,n)}function md5_ii(d,_,m,f,r,i,n){return md5_cmn(m^(_|~f),d,_,r,i,n)}function safe_add(d,_){var m=(65535&d)+(65535&_);return(d>>16)+(_>>16)+(m>>16)<<16|65535&m}function bit_rol(d,_){return d<<_|d>>>32-_}

console.log(MD5(plain));

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
