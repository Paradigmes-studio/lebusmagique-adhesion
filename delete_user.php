<?php
require_once("db/mUser.php"); 
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

$u = new mUser($conn, $conf);

$login_to_delete = $_GET['login'] ?? '';

if ($login_to_delete === 'admin') {
	header('Location: listUsers.php?error=Impossible de supprimer le compte admin');
	exit();
}

if ($login_to_delete === $user->login) {
	header('Location: listUsers.php?error=Impossible de supprimer ton propre compte');
	exit();
}

if ($login_to_delete === '') {
	header('Location: listUsers.php');
	exit();
}

$u->delete_by_login($login_to_delete);
header(sprintf('Location: listUsers.php?info=Utilisateur %s supprimé', urlencode($login_to_delete)));


?>

