<?php
require_once("db/mUser.php"); 
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

if ($_POST['password1'] == $_POST['password2']) {
	$user->set_password($_POST['password1']);
	$u = new mUser($conn, $conf);
	$u->write($user);
	header('Location: main.php?info=Password%20updated');
} else { 
	header('Location: changePassword.php?error=Les deux mots de passe ne sont pas identiques&mdp='.$_POST['password1']);
} 

?>
