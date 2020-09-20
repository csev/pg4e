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
// echo("<pre>\n");var_dump($pieces);die();

if ( strlen($pieces->controller) < 1 || strlen($pieces->controller) < 1 ) {
    echo("<p>This provides access to elastic search indexes for use with this course.</p>");
    return;
}

$apache_headers = apache_request_headers();
$curl_headers = array();
foreach($apache_headers as $key => $val ) {
   $curl_headers[] = $key.": ".$val;
}

// $base_url = 'http://localhost:9200';
$base_url = $CFG->elasticsearch_backend;
$method = $_SERVER['REQUEST_METHOD'];
$tail = $pieces->controller;
if ( $pieces->extra ) $tail .= '/' . $pieces->extra;
$tail = U::reconstruct_query($tail, $_GET);
$entityBody = file_get_contents('php://input');
$es_url = $base_url . '/' . $tail;
if ( strlen($entityBody) > 0 ) {
    error_log(substr($entityBody, 0, 100));
}
// error_log("$es_url");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $es_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 1);
if ( strlen($entityBody) > 0 ) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $entityBody);
}
$response = curl_exec($ch);

// https://beamtic.com/curl-response-headers
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

error_log("$method $tail input=".strlen($entityBody)." response code:".$httpCode." output=".strlen($body));

// echo("<pre>\n"); echo("Code: $httpCode\n"); echo($headers); echo("\n"); echo($body); echo("\n");
$headers_indexed_arr = explode("\r\n", $headers);
// Define as array before using in loop
$headers_arr = array();
// Remember the status message in a separate variable
$status_message = array_shift($headers_indexed_arr);

// Create an associative array containing the response headers
foreach ($headers_indexed_arr as $value) {
  if(false !== ($matches = explode(':', $value, 2))) {
    if ( count($matches) != 2 ) continue;
    $headers_arr["{$matches[0]}"] = trim($matches[1]);
  }
}

// Prepare the response
http_response_code($httpCode);

foreach($headers_arr as $key => $value) {
   header($key . ': ' . $value);
}

echo($body);


/*
 
 https://www.pg4e.com/elasticsearch/one/two/three/four

 object(stdClass)#5 (8) {
  ["parent"]=>
  string(14) "/elasticsearch"
  ["base_url"]=>
  string(20) "https://www.pg4e.com"
  ["controller"]=>
  string(3) "one"
  ["extra"]=>
  string(14) "two/three/four"
  ["action"]=>
  string(3) "two"
  ["parameters"]=>
  array(2) {
    [0]=>
    string(5) "three"
    [1]=>
    string(4) "four"
  }
  ["current"]=>
  string(18) "/elasticsearch/one"
  ["full"]=>
  string(33) "/elasticsearch/one/two/three/four"
}
 */
