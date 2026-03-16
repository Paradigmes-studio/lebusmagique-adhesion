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
<div class="page-title">Recherche</div>
<p class="page-subtitle">Remplis un ou plusieurs champs pour trouver un adhérent.</p>
<form action="listAdhesionClient.php" method="POST">
<div class="search-fields">
<?php
	printf('<input type="text" maxlength="10" name="adherent_id" value="%s" inputmode="numeric" placeholder="N&deg; adhérent"/>', htmlspecialchars($_GET['idAdherent'] ?? ''));
	printf('<input type="text" maxlength="200" name="last_name" value="%s" placeholder="Nom"/>', htmlspecialchars($_GET['lastName'] ?? ''));
	printf('<input type="text" maxlength="200" name="first_name" value="%s" placeholder="Prénom"/>', htmlspecialchars($_GET['firstName'] ?? ''));
	printf('<input type="email" maxlength="200" name="email" value="%s" placeholder="Email"/>', htmlspecialchars($_GET['email'] ?? ''));
?>
</div>
<p><input type="submit" value="&#128269; Rechercher"/></p>
<p><a href="main.php" class="page-back">Retour</a></p>
</form>
</div>
</body>
</html>
