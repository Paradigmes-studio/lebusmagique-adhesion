<?php
require_once("init.php");
require_once("get_login_info.php");
?>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="mobile.css">
</head>
<body class="defaultback">
<div class="page">
<div class="page-title">Mot de passe</div>
<form action="mChangePassword.php" method="post">
<?php if (isset($_GET['error'])): ?>
	<div class="alert alert--error"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>
<div class="search-fields">
	<input maxlength="200" type="password" name="password1" placeholder="Nouveau mot de passe" />
	<input maxlength="200" type="password" name="password2" placeholder="Confirmation" />
</div>
<p><input type="submit" value="Enregistrer"/></p>
<p><a href="main.php" class="page-back">Annuler</a></p>
</form>
</div>
</body>
</html>
