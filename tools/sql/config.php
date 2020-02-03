<?php
require_once "../config.php";
require_once "sql_util.php";

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;
use \Tsugi\UI\Lessons;

$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;
$unique = getUnique($LAUNCH);

if ( ! $USER->instructor ) die("Must be instructor");

$redirect = false;
$postkeys = array('db_source', 'psql_url', 'psql_secret', 'psql_key', 'tunnel');
foreach($postkeys as $key) {
    if ( U::get($_POST, $key) ) {
        $LAUNCH->context->settingsSet($key, U::get($_POST, $key));
        $redirect = true;
    }
}

if ( $redirect ) {
    $_SESSION['success'] = 'Settings updated.';
    header("Location: ".addSession('index.php'));
    return;
}

$settings = $LAUNCH->context->settingsGetAll();

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

// echo("<pre>\n");var_dump($set);echo("</pre>\n");

?>
<p>Please configure the database source(s) for this course.</p>
<form method="post">
<p>
<select name="db_source">
<option value="none">-- Please select the type of database server --</option>
<option value="umsi"
<?php if ( U::get($settings, "db_source") == 'umsi' ) echo('selected'); ?>
>UMSI</option>
<option value="elephant"
<?php if ( U::get($settings, "db_source") == 'elephant' ) echo('selected'); ?>
>ElephantSQL</option>
</select>
</p>
<p>If you are using ElephantSQL, please leave the PSQL provisioning values blank.</p>
<p>PSQL_URL <input type="text" name="psql_url" value="<?= htmlentities(U::get($settings, 'psql_url')) ?>"></p>
<p>PSQL_KEY <input type="text" name="psql_key" value="<?= htmlentities(U::get($settings, 'psql_key')) ?>"></p>
<p>PSQL_SECRET <input type="text" name="psql_secret" value="<?= htmlentities(U::get($settings, 'psql_secret')) ?>"></p>
<p>
Is there an ssh tunnel required?
<select name="tunnel">
<option value="no">No Tunnel<option>
<option value="yes"
<?php if ( U::get($settings, "tunnel") == 'yes' ) echo('selected'); ?>
>SSH Tunnel</option>
</select>
</p>
<input type="submit">
</form>

<?php


$OUTPUT->footer();

