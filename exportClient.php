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
<div class="form padded main">
<form action="mExportClient.php" method="POST">
Date de début d'export<br/>
<input type="date" name="begining" required/>
Date de fin d'export<br/>
<input type="date" name="end" required/>
<input type="submit" value="Export" ?>
<p><input type="button" onclick="location='main.php';" value="Retour" /></p>
</form>
</div>
</body>
</html>

