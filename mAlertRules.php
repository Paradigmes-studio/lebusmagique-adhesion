<?php
require_once("db/mAlertRule.php");
require_once("init.php");
require_once("get_login_info.php");

$err = "";
$values = "";

if (empty($_POST['name'])) {
	$err .= "nameErr=Il manque le nom de l'alerte&";
} else {
	$values .= "name=" . $_POST['name'] . "&";
}

if ($err != "") {
	$editing = isset($_POST['id']) ? '&id=' . $_POST['id'] : '';
	header(sprintf('Location: alertRules.php?%s%s%s', $err, substr_replace($values, "", -1), $editing));
	exit;
}

$manager = new mAlertRule($conn, $conf);
$alert_rule = new AlertRule();

if (isset($_POST['id']) && $_POST['id'] > 0) {
	$alert_rule->id = (int)$_POST['id'];
}

$alert_rule->name = $_POST['name'];
$alert_rule->trigger_type = $_POST['trigger_type'];
$alert_rule->trigger_days = ($alert_rule->trigger_type === 'on') ? 0 : (int)$_POST['trigger_days'];
$alert_rule->email_template = $_POST['email_template'];
$alert_rule->active = isset($_POST['active']) ? 1 : 0;

$manager->write($alert_rule);

header('Location: alertRules.php?success=Alerte enregistrée');
exit;
