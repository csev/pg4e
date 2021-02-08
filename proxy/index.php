<?php

use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);

require_once "../tsugi/config.php";

$legit = array(
    'https://swapi.py4e.com',
    'https://pokeapi.co',
    'https://mbox.dr-chuck.net',
    'http://mbox.dr-chuck.net',
    'http://www.gutenberg.org',
    'https://www.gutenberg.org',
);


if ( ! function_exists('endsWith') ) {
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}
}

$pieces = U::rest_path();
if ( strlen($pieces->controller) < 1 || strlen($pieces->extra) < 1 ) {
    echo("<p>This utility serves as a proxy to bypass the Coursera firewall.</p>");
    echo("<p>If you are trying to access a url like <pre>\n");
    echo("https://mbox.dr-chuck.net/sakai.devel/4/5\n\n");
    echo("You can try\n\n");
    echo("<b>".$CFG->apphome."/proxy</b>/http://mbox.dr-chuck.net/sakai.devel/4/5\n");
    echo("</pre>\n");
    return;
}

$query = http_build_query($_GET);
$url = $pieces->controller.'/'.$pieces->extra;
if ( strlen($query) > 0 ) $url .= '?' . $query;

$good = false;
foreach($legit as $ok_url) {
    if ( strpos($url, $ok_url) === 0 ) {
        $good = true;
    }
}

if ( ! $good ) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo("Your URL is not one of the allowed proxy destinations\n\n");
    echo($url."\n");
    return;
}

$header_lines = array();
// https://stackoverflow.com/questions/9183178/can-php-curl-retrieve-response-headers-and-body-in-a-single-request
function HandleHeaderLine( $curl, $header_line ) {
    global $header_lines;
    $header_lines[] = $header_line;
    return strlen($header_line);
}

// echo("<pre>\n"); var_dump($pieces);

// create curl resource
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADERFUNCTION, "HandleHeaderLine");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$output = curl_exec($ch);
if ( $output === false) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo("Could not retrieve\n".$url);
    return;
}
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpcode);

$to_copy = array(
    'Content-Type:',
    'Expires:',
    'Content-Length:'
);

$copied = array();
foreach($header_lines as $header) {
    foreach($to_copy as $copy) {
        if ( stripos($header, $copy) === 0 ) {
            $copied[] = $header;
            header($header);
            break;
        }
    }
}


// echo("<pre>\n"); var_dump($pieces); echo($url."\n"); echo($httpcode."\n");
// var_dump($copied); var_dump($header_lines);

echo($output);

