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
$postkeys = array('tunnel', 'proxy', 'db_source', 'umsi_url', 'umsi_password', 'umsi_key',
  'es_source', 'es_host', 'es_port', 'es_prefix', 'es_password', 'um_es_url', 'um_es_key', 'um_es_password');

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

$umsi_password = U::get($settings, 'umsi_password');
$um_es_password = U::get($settings, 'um_es_password');
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
<p>If you are using ElephantSQL or some other externally provisioned PostgreSQL
server, leave the UMSI provisioning values blank.
If this course using UMSI provisioning, please configure the API for this <b>course</b>.   
<form method="post">
<p>
<select name="db_source">
<option value="">-- Please select database server provisioning approach --</option>
<option value="none"
<?php if ( U::get($settings, "db_source") == 'none' ) echo('selected'); ?>
>None - Prompt the user</option>
<option value="umsi"
<?php if ( U::get($settings, "db_source") == 'umsi' ) echo('selected'); ?>
>UMSI</option>
<option value="elephant"
<?php if ( U::get($settings, "db_source") == 'elephant' ) echo('selected'); ?>
>ElephantSQL</option>
</select>
</p>
<p>UMSI_URL <input type="text" name="umsi_url" value="<?= htmlentities(U::get($settings, 'umsi_url')) ?>"></p>
<p>UMSI_KEY <input type="text" name="umsi_key" value="<?= htmlentities(U::get($settings, 'umsi_key')) ?>"></p>
<p>UMSI_PASSWORD 
<span id="pass" style="display:none"><input type="text" name="umsi_password" id="umsi_password" value="<?= htmlentities($umsi_password) ?>"/></span> (<a href="#" onclick="$('#pass').toggle();return false;">hide/show</a> <a href="#" onclick="copyToClipboard(this, '<?= htmlentities($umsi_password) ?>');return false;">copy</a>)</p>
<p>
<select name="es_source">
<option value="">-- Please select elastic server provisioning approach --</option>
<option value="none"
<?php if ( U::get($settings, "es_source") == 'none' ) echo('selected'); ?>
>None - Prompt the user</option>
<option value="umsi"
<?php if ( U::get($settings, "es_source") == 'umsi' ) echo('selected'); ?>
>UMSI</option>
<option value="proxy"
<?php if ( U::get($settings, "es_source") == 'proxy' ) echo('selected'); ?>
>ElasticProxy</option>
</select>
</p>
<p>UMSI ElasticSearch</p>
<p>UMSI_ES_URL <input type="text" name="um_es_url" value="<?= htmlentities(U::get($settings, 'um_es_url')) ?>"></p>
<p>UMSI_ES_KEY <input type="text" name="um_es_key" value="<?= htmlentities(U::get($settings, 'um_es_key')) ?>"></p>
<p>UMSI_ES_PASSWORD 
<span id="umespass" style="display:none"><input type="text" name="um_es_password" id="um_es_password" value="<?= htmlentities($um_es_password) ?>"/></span> (<a href="#" onclick="$('#umespass').toggle();return false;">hide/show</a> <a href="#" onclick="copyToClipboard(this, '<?= htmlentities($um_es_password) ?>');return false;">copy</a>)</p>
<p>ElasticProxy</p>
<p>PR_ES_HOST <input type="text" name="es_host" value="<?= htmlentities(U::get($settings, 'es_host')) ?>"></p>
<p>PR_ES_PORT <input type="text" name="es_port" value="<?= htmlentities(U::get($settings, 'es_port')) ?>"></p>
<p>PR_ES_PREFIX <input type="text" name="es_prefix" value="<?= htmlentities(U::get($settings, 'es_prefix')) ?>"></p>
<p>PR_ES_PASSWORD 
<span id="espass" style="display:none"><input type="text" name="es_password" id="es_password" value="<?= htmlentities($es_password) ?>"/></span> (<a href="#" onclick="$('#espass').toggle();return false;">hide/show</a> <a href="#" onclick="copyToClipboard(this, '<?= htmlentities($es_password) ?>');return false;">copy</a>)</p>
<p>
<p>
Proxy Instructions?
<select name="proxy">
<option value="no">No Proxy Instructions</option>
<option value="yes"
<?php if ( U::get($settings, "proxy") == 'yes' ) echo('selected'); ?>
>Provide Proxy Instructions</option>
</select>
</p>
<p>
Is there an ssh tunnel required?
<select name="tunnel">
<option value="no">No Tunnel</option>
<option value="yes"
<?php if ( U::get($settings, "tunnel") == 'yes' ) echo('selected'); ?>
>SSH Tunnel</option>
</select>
</p>
<input type="submit" name="update">
</form>

<?php
$OUTPUT->footer();

