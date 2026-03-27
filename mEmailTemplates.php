<?php
require_once("db/mEmailTemplate.php");
require_once("init.php");
require_once("get_login_info.php");

$err = "";
$values = "";

if (empty($_POST['name'])) {
	$err .= "nameErr=Il manque le nom du template&";
} else {
	$values .= "name=" . urlencode($_POST['name']) . "&";
}

if (empty($_POST['subject'])) {
	$err .= "subjectErr=Il manque le sujet de l'email&";
} else {
	$values .= "subject=" . urlencode($_POST['subject']) . "&";
}

if ($err != "") {
	$editing = isset($_POST['id']) ? '&id=' . $_POST['id'] : '';
	header(sprintf('Location: emailTemplates.php?%s%s%s', $err, substr_replace($values, "", -1), $editing));
	exit;
}

$manager = new mEmailTemplate($conn, $conf);
$template = new EmailTemplate();

if (isset($_POST['id']) && $_POST['id'] > 0) {
	$template->id = (int)$_POST['id'];
}

$template->name = $_POST['name'];
$template->subject = $_POST['subject'];
$template->body = $_POST['body'];

$manager->write($template);

header('Location: emailTemplates.php?success=Template enregistré');
exit;
