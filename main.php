<?php 
//require_once("db/user.php");

require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

?>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" /> 
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="mobile.css"> 
<script>

</script> 
</head>
<body class="defaultback"> 
<div class="center padded main">
</br><text class="title">Gestion des adhésions</text><br/><br/>
<p><input type="button" onclick="location.href='createAdhesionClient.php';" value="Nouvelle adhesion" /></p>
<p><input type="button" onclick="location.href='searchAdhesionClient.php';" value="Recherche adhérents" /></p>
<p><input type="button" onclick="location.href='exportClient.php';" value="Export adhérents" /></p>
<p><input type="button" onclick="location.href='listAdhesionType.php';" value="Types d'adhésions" /></p> 
<p><input type="button" onclick="location.href='tagMailchimp.php';" value="Tag Mailchimp" /></p> 
</br><text class="title">Gestion des utilisateurs</text><br/><br/>
<p><input type="button" onclick="location.href='listUsers.php';" value="Gestion des utilisateurs" /></p>
<p><input type="button" onclick="location.href='changePassword.php';" value="Changer ton mdp" /></p> 

<p><input type="button" onclick="location.href='mLogout.php';" value="Deconnexion" /></p> 

</div>
</body>
</html>
