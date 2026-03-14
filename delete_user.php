<?php
require_once("db/mUser.php"); 
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

$u = new mUser($conn, $conf);

$login_to_delete = $_GET['login'] ?? '';

if ($login_to_delete === 'admin') {
	header('Location: listUsers.php?error=Cannot delete admin');
	exit();
}

if ($login_to_delete === $user->login) {
	header('Location: listUsers.php?error=Cannot delete yourself');
	exit();
}

if ($login_to_delete === '') {
	header('Location: listUsers.php');
	exit();
}

$u->delete_by_login($login_to_delete);
header(sprintf('Location: listUsers.php?info=User %s removed', urlencode($login_to_delete)));


?>

