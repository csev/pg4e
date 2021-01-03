<?php

/**
 * These are some configuration variables that are not secure / sensitive
 *
 * This file is included at the end of tsugi/config.php
 */

$CFG->servicename = 'PG4E';
$CFG->servicedesc = 'Postgres for Everybody';

// Information on the owner of this system
$CFG->ownername = 'Charles Severance';
$CFG->owneremail = 'csev@umich.edu';
$CFG->providekeys = true;  // true

$CFG->context_title = "Postgres for Everybody";
$CFG->lessons = $CFG->dirroot.'/../lessons.json';
$CFG->giftquizzes = $CFG->dirroot.'/../pg4e-private/quiz';
$CFG->tdiscus = $CFG->apphome . '/mod/tdiscus/';

$CFG->theme = array(
    "primary" => "#336791", //default color for nav background, splash background, buttons, text of tool menu
    "secondary" => "#EEEEEE", // Nav text and nav item border color, background of tool menu
    "text" => "#111111", // Standard copy color
    "text-light" => "#5E5E5E", // A lighter version of the standard text color for elements like "small"
    "font-url" => "https://fonts.googleapis.com/css2?family=Open+Sans", // Optional custom font url for using Google fonts
    "font-family" => "'Open Sans', Corbel, Avenir, 'Lucida Grande', 'Lucida Sans', sans-serif", // Font family
    "font-size" => "14px", // This is the base font size used for body copy. Headers,etc. are scaled off this value
);

$buildmenu = $CFG->dirroot.'/../buildmenu.php';
if ( file_exists($buildmenu) ) {
    require_once $buildmenu;
    $CFG->defaultmenu = buildMenu();
}


