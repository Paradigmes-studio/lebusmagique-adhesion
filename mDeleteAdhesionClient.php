<?php
require_once("db/mAdhesionClient.php"); 
require_once("db/adhesionClient.php"); 
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect
require_once("lib/MailChimpHandler.php");

$a = new mAdhesionClient($conn, $conf);

$id = $_GET['id'];

$adhesion_client = new AdhesionClient();
$a->read($id, $adhesion_client);
$a->delete_by_id($id);

if ($adhesion_client->newsletter) {
	$mc = new MailChimpHandler($conn, $conf);
	$mc->manageEmailList($adhesion_client, '', 'DELETE');
}

header(sprintf('Location: listAdhesionClient.php')); 


?>

