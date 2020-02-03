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

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// All the assignments we support
$assignments = array(
    '00setup.php' => 'Setup database environment',
    '00first.php' => 'Add some housekeeping tables',
    '01single.php' => 'Single Table (ages)',
    '01auto.php' => 'Auto Increment / SERIAL (automagic)',
    '01single_track.php' => 'One table CSV (tracks_raw)',
    '02many_one_manual.php' => 'Many-to-One By Hand (autos)',
    '02many_one_track.php' => 'Many-to-One CSV (tracks)',
    '02many_one_unesco.php' => 'Many-to-One CSV (unesco)',
    '03many_to_many_roster.php' => 'Many-to-Many By Hand (roster)',
    '03many_to_many_track.php' => 'Many-to-Many CSV (tracks)',
    '03updated.php' => 'Stored Procedure',
    '04bigtext.php' => 'Text Scan',
    '05sqlstring.php' => 'Inverted index using SQL',
    '05sqlstop.php' => 'Inverted index with stop words using SQL',
    '05ginstring.php' => 'Inverted string index using GIN',
    '05fulltext.php' => 'Full text GIN using tsvector',
    '06pokeapi.php' => 'Loading JSON from PokeAPI',
    '07tweet.php' => 'Elastic Search Tweets',
    '07book.php' => 'Elastic Search Book Text',
    '07gmane.php' => 'Elastic Search Email',
);

$oldsettings = Settings::linkGetAll();

$assn = Settings::linkGet('exercise');

$tunnel = $LAUNCH->link->settingsGet('tunnel');

$custom = LTIX::ltiCustomGet('exercise');

if ( $assn && isset($assignments[$assn]) ) {
    // Configured
} else if ( strlen($custom) > 0 && isset($assignments[$custom]) ) {
    Settings::linkSet('exercise', $custom);
    $assn = $custom;
}

if ( $assn === false && isset($_GET["inherit"]) && isset($CFG->lessons) ) {
    $l = new Lessons($CFG->lessons);
    if ( $l ) {
        $lti = $l->getLtiByRlid($_GET['inherit']);
        if ( isset($lti->custom) ) foreach($lti->custom as $custom ) {
            if (isset($custom->key) && isset($custom->value) && $custom->key == 'exercise' ) {
                $assn = $custom->value;
                Settings::linkSet('exercise', $assn);
            }
        }
    }
}

// Get any due date information
$dueDate = SettingsForm::getDueDate();

// Let the assignment handle the POST
if ( ( count($_FILES) + count($_POST) ) > 0 && 
    $assn && isset($assignments[$assn]) ) {
    include($assn);
    return;
}

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();

// Settings button and dialog

echo('<div style="float: right;">');
if ( $USER->instructor ) {
    if ( $CFG->launchactivity ) {
        echo('<a href="analytics" class="btn btn-default">Launches</a> ');
    }
    echo('<a href="config.php"><button class="btn btn-info">Configure</button></a> '."\n");
    echo('<a href="grades.php" target="_blank"><button class="btn btn-info">Grade detail</button></a> '."\n");
}
SettingsForm::button();
$OUTPUT->exitButton();
echo('</div>');

$tunnelopts = array(
    "no" => __('Not using a tunnel'),
    "yes" => __('Using a tunnel'),
);

SettingsForm::start();
SettingsForm::select("exercise", __('Please select an assignment'),$assignments);
SettingsForm::select("tunnel", __('Are you using a tunnel'),$tunnelopts);
SettingsForm::dueDate();
SettingsForm::done();
SettingsForm::end();

if ( isset($_SESSION['error']) ) {
    $RESULT->setNote($_SESSION['error']);
} else if ( isset($_SESSION['success']) ) {
    $RESULT->setNote($_SESSION['success']);
}

$OUTPUT->flashMessages();

$OUTPUT->welcomeUserCourse();

$FOOTER_DONE = false;
if ( $assn && isset($assignments[$assn]) ) {
    include($assn);
} else {
    if ( $USER->instructor ) {
        echo("<p>Please use settings to select an assignment for this tool.</p>\n");
    } else {
        echo("<p>This tool needs to be configured - please see your instructor.</p>\n");
    }
}
        
if ( ! $FOOTER_DONE ) $OUTPUT->footer();

