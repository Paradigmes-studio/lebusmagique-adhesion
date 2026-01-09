<?php
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
<div class="padded main">
<form action="mChangePassword.php" method="post">
</br><text class="title">Modification de ton mot de passe</text><br/><br/>
<?php

	$error = isset($_GET['error']);
	if ($error) {
		printf('<p><input maxlength="200" type="password" name="password1" value="%s" placeholder="Nouveau mot de passe" /></p>', $_GET['mdp']);
		printf('<text class = "TextError">%s</text><br/>', $_GET['error']);
		printf('<p><input class="FieldError" maxlength="200" type="password" name="password2" placeholder="Confirmation" /></p>');
	} else {
		printf('<p><input maxlength="200" type="password" name="password1" placeholder="Nouveau mot de passe" /></p>');
		printf('<p><input maxlength="200" type="password" name="password2" placeholder="Confirmation" /></p>'); 
	}

?>
<p><input type="submit" value="Enregistrer"/></p>
</form>
<p><input type="button" onclick="location.href='main.php';" value="Annuler" /></p> 
</div>
</body>
</html>
