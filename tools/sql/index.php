<?php
// Local composer dependencies
if ( file_exists(__DIR__ . '/vendor/autoload.php') ) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    die('You need to run composer in '.__DIR__);
}

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
    '00first.php' => 'Our first tables',
    '01single.php' => 'Single Table (ages)',
    '01auto.php' => 'Auto Increment / SERIAL (automagic)',
    '01single_track.php' => 'One table CSV (tracks_raw)',
    '02many_one_manual.php' => 'Many-to-One By Hand (autos)',
    '02many_one_track.php' => 'Many-to-One CSV (tracks)',
    '02many_one_unesco.php' => 'Many-to-One CSV (unesco)',
    '03many_to_many_roster.php' => 'Many-to-Many By Hand (roster)',
    '03many_to_many_track.php' => 'Many-to-Many CSV (tracks+artists)',
    '03updated.php' => 'Stored Procedure',
    '03alter.php' => 'Alter Table',
    '03distinct.php' => 'Select Distinct',
    '03regexp.php' => 'Regular Expressions',
    '04bigtext.php' => 'Generating Text',
    '05sqlstring.php' => 'Inverted index using SQL',
    '05sqlstop.php' => 'Inverted index with stop words using SQL',
    '05ginstring.php' => 'Inverted string array index using GIN',
    '05fulltext.php' => 'Full text GIN using tsvector',
    '06simple.php' => 'Making simple.py work',
    '06pokeapi.php' => 'Loading JSON from PokeAPI',
    '07tweet.php' => 'Elasticsearch Tweets',
    '07book.php' => 'Elasticsearch Book Text',
    '07gmane.php' => 'Elasticsearch Email',
);

$oldsettings = Settings::linkGetAll();

$assn = Settings::linkGet('exercise');

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

$menu = false;
if ( $LAUNCH->user->instructor ) {
    $menu = new \Tsugi\UI\MenuSet();
    $menu->addLeft(__('Grade Detail'), 'grades.php');
    $menu->addRight(__('Launches'), 'analytics');
    $menu->addRight(__('Configure'), 'config.php');
    $menu->addRight(__('Settings'), '#', /* push */ false, SettingsForm::attr());
}

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);

SettingsForm::start();
SettingsForm::select("exercise", __('Please select an assignment'),$assignments);
SettingsForm::dueDate();
SettingsForm::done();
SettingsForm::end();

if ( isset($_SESSION['error']) ) {
    $RESULT->setNote($_SESSION['error']);
} else if ( isset($_SESSION['success']) ) {
    $RESULT->setNote($_SESSION['success']);
}

$OUTPUT->flashMessages();

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

