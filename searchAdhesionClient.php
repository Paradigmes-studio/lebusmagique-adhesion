<?php
require_once("init.php");
?>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" /> 
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="mobile.css"> 

<style>
.input_date {
	padding: 0px !important;
	margin: 0px !important;
	font-size: 0.9rem !important;
}
</style>
</head>
<body class="defaultback"> 
<div class="form padded main">
<form action="listAdhesionClient.php" onsubmit="exceptionsTableToArray()" method="POST">
<?php

	print('N° adhérent<br/>');
	printf('<input type="text" maxlength="10" name="adherent_id" value="%s" placeholder="N° adhérent"/>', $_GET['idAdherent']); 

	print('Nom<br/>');
	printf('<input type="text" maxlength="200" name="last_name" value="%s" placeholder="Nom"/>', $_GET['lastName']); 

	print('Prénom<br/>');
	printf('<input type="text" maxlength="200" name="first_name" value="%s" placeholder="Prénom"/>', $_GET['firstName']); 

	print('Email<br/>');		
	printf('<input type="text" maxlength="200" name="email" value="%s" placeholder="Email"/>', $_GET['email']); 

?>
<p><input type="submit" value="Rechercher"/></p>
<p><input type="button" onclick="location.href='main.php';" value="Retour" /></p> 

</form>

</div>
</body>
<script>
exceptionsArrayToTable();
</script>
</html>
