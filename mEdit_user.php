<?php
require_once("db/mUser.php");
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

$err=""; 
if ($_POST['login'] == '') {
	$err = "Login requis";
}
if (($_POST['new']) && ($_POST['password'] == '')) {
	$err = "Mot de passe requis";
} 
if ($err != '') {
	header(sprintf('Location: editUser.php?error=%s', urlencode($err)));
	exit();
}
$edited_user = new User();

$u = new mUser($conn, $conf);
if (!$_POST['new']) {
	if (!$u->read($_POST['login'], $edited_user)) {
		header('Location: editUser.php?error=Utilisateur introuvable'); 
		exit;
	}
} else {
	$edited_user->login=$_POST['login'];
	if ($u->read($_POST['login'], $edited_user))  {
		header('Location: editUser.php?error=Cet utilisateur existe déjà'); 
		exit;
	}
}
if ($_POST['password'] != '') {
	$edited_user->set_password($_POST['password']);
}

$u->write($edited_user);

header(sprintf('Location: listUsers.php')); 

?>
