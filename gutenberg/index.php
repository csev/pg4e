<?php

use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);

require_once "../tsugi/config.php";

if ( ! function_exists('endsWith') ) {
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}
}

$pieces = U::rest_path();
if ( strlen($pieces->controller) < 1 || strlen($pieces->extra) < 1 ) {
    echo("<p>This url retrieves text documents from www.gutenberg.org.</p>");
    echo("<p>You can retrieve documents of the form:\n<pre>\n");
    echo($CFG->apphome . "/gutenberg/cache/epub/514/pg514.txt\n");
    return;
}

if ( ! endsWith($pieces->extra, '.txt') ) {
    echo("This only retrieves .txt files");
    return;
}

// echo("<pre>\n");var_dump($pieces);

$url = "http://www.gutenberg.org/".$pieces->controller.'/'.$pieces->extra;

$content = file_get_contents($url);
header('Content-Type: text/plain');
echo($content);

