<?php
require_once "../config.php";

use \Tsugi\Core\LTIX;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();


// Render view
$OUTPUT->header();
$OUTPUT->bodyStart();
echo("<h1>Under Construction...</h1>\n");
$OUTPUT->footer();
