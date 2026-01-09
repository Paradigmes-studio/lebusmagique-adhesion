<?php
require_once("db/mUser.php");
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

?>

<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" /> 
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="mobile.css"> 
</head>
<body class="defaultback"> 
<div class="form padded main">
<form action="mEdit_user.php" method="POST">
<?php
	$edited_user = new User();
	$new = !isset($_GET['login']);
	if (!$new) { 
		$u = new mUser($conn, $conf);
		if (!$u->read($_GET['login'], $edited_user)) {
			header('Location: users.php?error=cannot find user');
		}
	} 
	if (!$new) {
		printf('<div class="text">Login: <i>%s</i></div>', $edited_user->login);
		printf('<input type="hidden" name="login" value="%s">', $edited_user->login);
	} else {
		print('<input maxlength="50" type="text" name="login" placeholder="Nom"/>');
		printf('<input type="hidden" name="new" value="1">');
	}
	printf('<input type="hidden" name="new" value="%d">', $new);
	print('<input type="text" maxlength="200" name="password" placeholder="Mot de passe"/>');

?>
<p><input type="submit" value="Enregistrer"/></p>
</form>
<p><input type="button" onclick="location.href='listUsers.php';" value="Annuler" /></p> 
</div>
</body>
</html>


