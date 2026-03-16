<?php
require_once("db/mUser.php");
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
<?php
	$edited_user = new User();
	$new = !isset($_GET['login']);
	if (!$new) {
		$u = new mUser($conn, $conf);
		if (!$u->read($_GET['login'], $edited_user)) {
			header('Location: listUsers.php?error=Utilisateur introuvable');
			exit();
		}
	}
?>
<div class="page-title"><?php echo $new ? 'Nouvel utilisateur' : 'Modifier ' . htmlspecialchars($edited_user->login); ?></div>
<form action="mEdit_user.php" method="POST">
<?php if (!$new): ?>
	<input type="hidden" name="login" value="<?php echo htmlspecialchars($edited_user->login); ?>">
<?php else: ?>
	<input maxlength="50" type="text" name="login" placeholder="Login" autofocus />
<?php endif; ?>
	<input type="hidden" name="new" value="<?php echo (int)$new; ?>">
	<input type="password" maxlength="200" name="password" placeholder="<?php echo $new ? 'Mot de passe' : 'Nouveau mot de passe (laisser vide pour ne pas changer)'; ?>" />
<p><input type="submit" value="Enregistrer"/></p>
<p><a href="listUsers.php" class="page-back">Annuler</a></p>
</form>
</div>
</body>
</html>


