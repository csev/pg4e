<?php
require_once "../config.php";
require_once "sql_util.php";

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;
use \Tsugi\UI\Lessons;

$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;
$unique = getUnique($LAUNCH);

if ( ! $USER->instructor ) die("Must be instructor");

$oldsettings = Settings::linkGetAll();

$assn = Settings::linkGet('exercise');

$tunnel = $LAUNCH->link->settingsGet('tunnel');

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();

// Settings button and dialog

echo('<div style="float: right;">');
echo('<a href="index.php"><button class="btn btn-info">Back</button></a> '."\n");
echo('</div>');

$OUTPUT->flashMessages();

$OUTPUT->welcomeUserCourse();

echo("<h1>Yada</h1>\n");

$OUTPUT->footer();

