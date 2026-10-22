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
$postkeys = array('pg_host', 'pg_port', 'readonly_db', 'readonly_user', 'readonly_password');

if ( U::get($_POST, 'update') ) {
    foreach($postkeys as $key) {
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

$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft(__('Back'), 'index.php');

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();

// echo("<pre>\n");var_dump($set);echo("</pre>\n");

?>
<p><b>Note:</b> This is a per-course configuration, not a per-link
configuration so <b>changing this configuration</b>
affects all of the links in a course.  So be careful.
</p>
<form method="post">
<p>The PostgreSQL server is internally provisioned but the host and port shown to the student may be different.
Fill in these fields if you want to affect what the student sees in their <b>psql</b> commands.
</p>
<p>PG_HOST <input type="text" name="pg_host" value="<?= htmlentities(U::get($settings, 'pg_host')) ?>"></p>
<p>PG_PORT <input type="text" name="pg_port" value="<?= htmlentities(U::get($settings, 'pg_port')) ?>"></p>
<p>READONLY_DB <input type="text" name="readonly_db" value="<?= htmlentities(U::get($settings, 'readonly_db')) ?>"></p>
<p>READONLY_USER <input type="text" name="readonly_user" value="<?= htmlentities(U::get($settings, 'readonly_user')) ?>"></p>
<p>READONLY_PASSWORD <input type="text" name="readonly_password" value="<?= htmlentities(U::get($settings, 'readonly_password')) ?>"></p>
<input type="submit" name="update">
</form>

<?php
$OUTPUT->footer();

