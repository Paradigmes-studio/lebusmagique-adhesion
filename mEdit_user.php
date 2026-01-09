<?php
require_once("db/mUser.php");
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

$err=""; 
if ($_POST['login'] == '') {
	$err = "Login required";
}
if (($_POST['new']) && ($_POST['password'] == '')) {
	$err = "Password required";
} 
if ($err != '') {
	header(sprintf('Location: editUser.php?error=%s', $err)); 
} 
$edited_user = new User();

$u = new mUser($conn, $conf);
if (!$_POST['new']) {
	if (!$u->read($_POST['login'], $edited_user)) {
		header('Location: editUser.php?error=User not found'); 
		exit;
	}
} else {
	$edited_user->login=$_POST['login'];
	if ($u->read($_POST['login'], $edited_user))  {
		header('Location: editUser.php?error=User already exists'); 
		exit;
	}
}
if ($_POST['password'] != '') {
	$edited_user->set_password($_POST['password']);
}

$u->write($edited_user);

header(sprintf('Location: listUsers.php')); 

?>
