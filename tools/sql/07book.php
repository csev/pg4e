<?php

$the_version = $_GET['es_version'] ?? $_COOKIE['es_version'] ?? 'elastic6';

setcookie("es_version", $the_version, time()+31556926 ,'/');
if ( $the_version == 'elastic7' ) {
    require_once("07book7.php");
} else {
    require_once("07book6.php");
}
