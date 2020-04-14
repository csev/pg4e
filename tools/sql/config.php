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
$postkeys = array('es_source', 'es_host', 'es_port', 'es_prefix', 'es_password');

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

$es_password = U::get($settings, 'es_password');

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
<p>
<select name="es_source">
<option value="">-- Please select elastic server provisioning approach --</option>
<option value="none"
<?php if ( U::get($settings, "es_source") == 'none' ) echo('selected'); ?>
>None - Prompt the user</option>
<option value="proxy"
<?php if ( U::get($settings, "es_source") == 'proxy' ) echo('selected'); ?>
>ElasticProxy</option>
</select>
</p>
<p>ElasticProxy</p>
<p>PR_ES_HOST <input type="text" name="es_host" value="<?= htmlentities(U::get($settings, 'es_host')) ?>"></p>
<p>PR_ES_PORT <input type="text" name="es_port" value="<?= htmlentities(U::get($settings, 'es_port')) ?>"></p>
<p>PR_ES_PREFIX <input type="text" name="es_prefix" value="<?= htmlentities(U::get($settings, 'es_prefix')) ?>"></p>
<p>PR_ES_PASSWORD 
<span id="espass" style="display:none"><input type="text" name="es_password" id="es_password" value="<?= htmlentities($es_password) ?>"/></span> (<a href="#" onclick="$('#espass').toggle();return false;">hide/show</a> <a href="#" onclick="copyToClipboard(this, '<?= htmlentities($es_password) ?>');return false;">copy</a>)</p>
</p>
<input type="submit" name="update">
</form>

<?php
$OUTPUT->footer();

