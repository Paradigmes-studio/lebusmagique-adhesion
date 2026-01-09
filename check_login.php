<?php
require_once("db/user.php");
require_once("db/mUser.php"); 
require_once("init.php");

$user=new User();
$u=new mUser($conn, $conf);

$u->read($_POST['login'], $user);

if ($user !== null) {
	if (password_verify($_POST['password'], $user->password)) { 
		$_SESSION[$domain.'user'] = serialize($user);
		header('Location: main.php');
	} else {
		header('Location: login.php?error=Wrong id/password');
		exit();
	}
} else { 
	header('Location: login.php?error=Wrong id/password');
	exit();
}

?>
