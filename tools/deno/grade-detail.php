<?php
require_once "../config.php";
\Tsugi\Core\LTIX::getConnection();

use \Tsugi\Grades\GradeUtil;

session_start();

// Get the user's grade data also checks session
$row = GradeUtil::gradeLoad($_REQUEST['user_id']);

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

// Show the basic info for this user
GradeUtil::gradeShowInfo($row);

if ( strlen($row['json']) > 0 ) {
	$json = json_decode($row['json']);
	if ( is_object($json) ) {
		if ( isset($json->port) && isset($json->ip) ) {
        	$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
        	socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 2, 'usec' => 2000));
        	$ip = $json->ip;
        	$port = $json->port;
			echo("<p>Checking the host/post connectivity...</p>\n");
      		$result = @socket_connect($socket, $ip, $port);
			if ( $result ) {
        		print "Successfully connected via socket to $ip:$port \n";
			} else {
 				print("<b>Could not connect to</b> $ip:$port error: ".htmlentities(socket_strerror(socket_last_error()))."\n");
			}
		}
		if ( isset($json->ip) && isset($json->user) && isset($json->password) && isset($json->port) ) {
			echo("<p>PostgreSQL root user:</p>\n");
 			echo("<pre>\n");
			echo("psql -h ".htmlentities($json->ip)." -p ".$json->port." -U ".htmlentities($json->user)."\n");
			echo('<span id="pass" style="display:none">'.htmlentities($json->password).'</span> (<a href="#" onclick="$(\'#pass\').toggle();return false;">hide/show</a> ');
			echo('<a href="#" onclick="copyToClipboard(this, $(\'#pass\').text());return false;">copy</a>');
			echo(')'."\n");
 			echo("</pre>\n");
		}
		if ( isset($json->ip) && isset($json->dbuser) && isset($json->dbpass) && isset($json->port) ) {
			echo("<p>PostgreSQL normal user:</p>\n");
 			echo("<pre>\n");
			echo("psql -h ".htmlentities($json->ip)." -p ".$json->port." -U ".htmlentities($json->dbuser)." pg4e\n");
			echo('<span id="dbpass" style="display:none">'.htmlentities($json->dbpass).'</span> (<a href="#" onclick="$(\'#dbpass\').toggle();return false;">hide/show</a> ');
			echo('<a href="#" onclick="copyToClipboard(this, $(\'#dbpass\').text());return false;">copy</a>');
			echo(')'."\n");
 			echo("</pre>\n");
		}
    }
}

if ( isset($row['note']) ) {
    echo("<p>Note:</p>\n<pre>\n");
    echo(htmlentities($row['note']));
    echo("</pre>\n");
}

if ( strlen($row['json']) > 0 ) {
    $json = json_decode($row['json']);
    $code = false;
    if ( isset($json->code) ) {
        $code = $json->code;
        unset($json->code);
    }
	echo('<p>JSON: <a href="#" onclick="$(\'#json\').toggle();return false;">hide/show</a></p>');
	echo("<pre id=\"json\" style=\"display:none;\">\n");
    echo(htmlentities($row['json']));
    echo("</pre>\n");
    if ( $code ) {
	    echo('<p>Code: <a href="#" onclick="$(\'#code\').toggle();return false;">hide/show</a></p>');
	    echo("<pre id=\"code\" style=\"display:none;\">\n");
        echo(htmlentities($code));
        echo("</pre>\n");
    }
}

$OUTPUT->footer();
