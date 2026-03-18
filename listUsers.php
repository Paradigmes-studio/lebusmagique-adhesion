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
<script src='lib/utils.js'></script>
</head>
<body class="defaultback">
<div class="page">
<div class="page-title">Utilisateurs</div>
<?php
	$u = new mUser($conn, $conf);
	$edited_users = $u->list_users();
	foreach ($edited_users as $edited_user) {
		$is_self = ($edited_user->login == $user->login);
?>
	<div class="result-card">
		<div class="result-card-body" <?php if (!$is_self) printf('onclick="location=\'editUser.php?login=%s\'"', htmlspecialchars($edited_user->login)); ?>>
			<div class="result-card-name"><?php echo htmlspecialchars($edited_user->login); ?></div>
			<?php if ($is_self) echo '<div class="result-card-detail">C\'est toi</div>'; ?>
		</div>
		<?php if (!$is_self): ?>
		<button type="button" onclick="confirm_action('Supprimer <?php echo htmlspecialchars($edited_user->login); ?> ?', 'delete_user.php?login=<?php echo htmlspecialchars($edited_user->login); ?>')" class="result-card-delete">&#10005;</button>
		<?php endif; ?>
	</div>
<?php } ?>
<p><a href="editUser.php" class="btn btn--add">+ Nouvel utilisateur</a></p>
<p><a href="main.php" class="page-back">Retour</a></p>
</div>
</body>
</html>
