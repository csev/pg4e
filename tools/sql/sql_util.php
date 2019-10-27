<?php

use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";
require_once "courses.php";

function makeRoster($code,$course_count=false,$name_count=false) {
    global $names, $courses;
    $MT = new Mersenne_Twister($code);
    $retval = array();
    $cc = 0;
    foreach($courses as $k => $course) {
    $cc = $cc + 1;
    if ( $course_count && $cc > $course_count ) break;
        $new = $MT->shuffle($names);
        $new = array_slice($new,0,$MT->getNext(17,53));
        $inst = 1;
        $nc = 0;
        foreach($new as $k2 => $name) {
            $nc = $nc + 1;
            if ( $name_count && $nc > $name_count ) break;
            $retval[] = array($name, $course, $inst);
            $inst = 0;
        }
    }
    return $retval;
}

// Unique to user + course
function getUnique($LAUNCH) {
    return md5($LAUNCH->user->key.'::'.$LAUNCH->context->key.'::'.$LAUNCH->user->id.'::'.$LAUNCH->context->id);
}

function pg4e_info($dbname) { 
    global $CFG;

    $dbname = 'zap123';
    $endpoint = $CFG->pg4e_api_url.'/info/'.$dbname;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $CFG->pg4e_api_key.':'.$CFG->pg4e_api_password);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    $result = curl_exec($ch);
    if($result === false)
    {
        return 'Curl error: ' . curl_error($ch);
    }                                                                                                      
    $returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo $returnCode."</br>";
    if ( $returnCode == 401 ) return "401: Not Authorized";
    if ( $returnCode == 500 || $returnCode == 404 ) return 404;
    if ( $returnCode != 200 ) return $returnCode . ": HTTP Error";

    // Lets parse the JSON
    $retval = json_decode($result, false);  // As stdClass
    if ( $retval == null ) {
        error_log("JSON Error: ".json_last_error_msg());
        error_log($result);
        return "JSON Error: ".json_last_error_msg();
    }
    return $retval;
}

function pg4e_extract_info($info) {
    $user = false;
    $password = false;
    $ip = false;
    try {
	$retval = new \stdClass();
 	$retval->user = base64_decode($info->auth->data->POSTGRES_USER);
 	$retval->password = base64_decode($info->auth->data->POSTGRES_PASSWORD);
 	$retval->ip = $info->svc->status->loadBalancer->ingress[0]->ip;
	return $retval;
    } catch(Exception $e) {
	return null;
    }
        

}

