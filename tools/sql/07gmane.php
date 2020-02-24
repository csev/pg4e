<?php
$get_version = $_GET['es_version'] ?? false;
$cookie_version = $_COOKIE['es_version'] ?? false;

$the_version = $_GET['es_version'] ?? $_COOKIE['es_version'] ?? 'elastic6';

// When we are switching - wipe out the cookies
if ( strlen($get_version) > 0 && $get_version != $cookie_version ) {
    $vars = array("es_host", "es_prefix", "es_port", "es_user", "es_pass");
    foreach($vars as $var) {
        setcookie($var, '', time()+31556926 ,'/');
        unset($_SESSION[$var]);
        unset($_COOKIE[$var]);
    }
    $the_version = $_GET['es_version'] ?? 'elastic6';
}

setcookie("es_version", $the_version, time()+31556926 ,'/');
if ( $the_version == 'elastic7' ) {
    require_once("07gmane7.php");
} else {
    require_once("07gmane6.php");
}
