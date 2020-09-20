<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);

require_once "../tsugi/config.php";
require_once "../tools/sql/sql_util.php";

if ( ! function_exists('endsWith') ) {
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}
}

$p = $CFG->dbprefix;

$pieces = U::rest_path();
// echo("<pre>\n");var_dump($pieces);die();

if ( strlen($pieces->controller) < 1 || strlen($pieces->controller) < 1 ) {
    echo("<p>This provides access to elastic search indexes for use with this course.</p>");
    return;
}
// Check Basic Authentication
$auth_user = U::get($_SERVER, 'PHP_AUTH_USER');
$auth_pw = U::get($_SERVER, 'PHP_AUTH_PW');

$backend_pw = $CFG->elasticsearch_password;
$es_pass = es_makepw($auth_user, $backend_pw);

if ( $es_pass != $auth_pw ) {
    header('HTTP/1.0 403 Forbidden');
    error_log("403 user=$auth_user pw=$auth_pw backend=$es_pass");
    return;
}

// Note the access
$PDOX = LTIX::getConnection();
$sql = "INSERT INTO ${p}elastic_access (index_name, updated_at) VALUES (:name, NOW())
   ON DUPLICATE KEY UPDATE updated_at=NOW();";
$values = array(":name" => $pieces->controller);
$PDOX->queryDie($sql, $values);

// Pull in all the data from the input request
$request_headers = apache_request_headers();
$curl_headers = array();
foreach($request_headers as $key => $val ) {
   $curl_headers[] = $key.": ".$val;
}

$base_url = $CFG->elasticsearch_backend;
$request_method = $_SERVER['REQUEST_METHOD'];
$tail = $pieces->controller;
if ( $pieces->extra ) $tail .= '/' . $pieces->extra;
$tail = U::reconstruct_query($tail, $_GET);
$request_body = file_get_contents('php://input');
$request_url = $base_url . '/' . $tail;

// Make the proxy request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $request_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method);
curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 1);
if ( strlen($request_body) > 0 ) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
}
$response = curl_exec($ch);

// Extract the response headers
// https://beamtic.com/curl-response-headers
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$response_body = substr($response, $header_size);
$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ( strlen($request_body) > 0 ) {
    error_log(substr($request_body, 0, 100));
}

$headers_indexed_arr = explode("\r\n", $headers);
$status_message = array_shift($headers_indexed_arr);
// Create an associative array containing the response headers
$response_headers = array();
foreach ($headers_indexed_arr as $value) {
  if(false !== ($matches = explode(':', $value, 2))) {
    if ( count($matches) != 2 ) continue;
    $response_headers["{$matches[0]}"] = trim($matches[1]);
  }
}

// Log the access
error_log("$request_method $tail input=".strlen($request_body)." response code:".$response_code." output=".strlen($request_body));
$sql = "INSERT INTO ${p}elastic_log
    (index_name, auth_user, auth_pw,
    request_url, request_body, request_headers,
    response_code, response_body, response_headers)
    VALUES
    (:index_name, :auth_user, :auth_pw,
    :request_url, :request_body, :request_headers,
    :response_code, :response_body, :response_headers);";
$values = array(
    ":index_name" => $pieces->controller,
    ":auth_user" => $auth_user,
    ":auth_pw" => $auth_pw,
    ":request_url" => $request_url,
    ":request_body" => $request_body,
    ":request_headers" => json_encode($request_headers),
    ":response_code" => $response_code,
    ":response_body" => $response_body,
    ":response_headers" => json_encode($response_headers),
);
$PDOX->queryDie($sql, $values);

// Send back the response
http_response_code($response_code);

foreach($response_headers as $key => $value) {
   header($key . ': ' . $value);
}

echo($response_body);


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
