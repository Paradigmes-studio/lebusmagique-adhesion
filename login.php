<?php
require_once("init.php");
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
<div class="page-header">
	<img src="res/logo.png" alt="Le Bus Magique" class="dashboard-logo">
</div>
<form action="check_login.php" method="post">
<?php if (isset($_GET['error'])): ?>
	<div class="alert alert--error"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>
<p><input maxlength="50" type="text" name="login" placeholder="Login" autofocus /></p>
<p><input maxlength="200" type="password" name="password" placeholder="Mot de passe" /></p>
<p><input type="submit" value="Connexion"/></p>
</form>
</div>
</body>
</html>
