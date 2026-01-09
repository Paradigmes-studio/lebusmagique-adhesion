<?php
$user=NULL;
if (isset($_SESSION)) {
	if (isset($_SESSION[$domain.'user'])) {
		$user=unserialize($_SESSION[$domain.'user']);
	}
}
if ($user==NULL) {
	header('Location: login.php?error=Disconnected');
	exit();
}


?>
