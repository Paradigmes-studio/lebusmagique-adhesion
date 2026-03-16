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
<div class="page-title">Export</div>
<p class="page-subtitle">Exporte les adhérents inscrits entre deux dates au format CSV.</p>
<form action="mExportClient.php" method="POST">
<div class="search-fields">
	<label class="field-label">Date de debut</label>
	<input type="date" name="begining" required/>
	<label class="field-label">Date de fin</label>
	<input type="date" name="end" required/>
</div>
<p><input type="submit" value="&#128196; Exporter en CSV"/></p>
<p><a href="main.php" class="page-back">Retour</a></p>
</form>
</div>
</body>
</html>

