<?php
require_once("db/mAdhesionClient.php"); 
require_once("db/adhesionClient.php"); 
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect
require_once("lib/MailChimpHandler.php");

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: main.php');
	exit();
}

$a = new mAdhesionClient($conn, $conf);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
	header('Location: searchAdhesionClient.php');
	exit();
}

$adhesion_client = new AdhesionClient();
if (!$a->read($id, $adhesion_client)) {
	header('Location: searchAdhesionClient.php');
	exit();
}
$a->delete_by_id($id);

if ($adhesion_client->newsletter) {
	$mc = new MailChimpHandler($conn, $conf);
	$mc->manageEmailList($adhesion_client, '', 'DELETE');
}

header(sprintf('Location: listAdhesionClient.php')); 


?>

