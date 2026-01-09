<?php 
require_once("db/mAdhesionType.php");
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

$t = new mAdhesionType($conn, $conf); 
$id = $_GET['id'];
$tt = new AdhesionType();
$t->read($id, $tt);

if ($t->get_used_in_adhesion_client($id)) {
	header(sprintf('Location: listAdhesionType.php?error=Cannot remove %s, in use', $tt->name)); 
} else { 
	$t->delete_by_id($_GET['id']);
	header(sprintf('Location: listAdhesionType.php?info=Adhesion Type %s removed', $tt->name)); 
}

?>
