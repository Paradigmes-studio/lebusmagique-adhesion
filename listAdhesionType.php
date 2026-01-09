<?php
require_once("db/mAdhesionType.php");
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

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
<div class="list main">
<table style="width: 100%">
<tr><th style="width: 50%">Name</th><th>Operation</th></tr>

<?php
	$t = new mAdhesionType($conn, $conf);
	$adhesion_types = $t->list_adhesion_type();
	foreach ($adhesion_types as $adhesion_type) {
		printf("<tr>");
		printf("<td>%s</td>", $adhesion_type->name);
		printf("<td style=\"text-align:center;\">");
		printf("<button onclick=\"location='editAdhesionType.php?id=%d'\" class=\"letter_button\">E</button>", $adhesion_type->id);
		printf("<button type=\"button\" onclick=\"confirm_action('Etes vous sûr de vouloir supprimer le type d adhésion %s?', 'deleteAdhesionType.php?id=%d')\" class=\"letter_button_red\">X</button>", $adhesion_type->name, $adhesion_type->id);
		printf("</tr>");
	}
?>
</table>
<button onclick="location='editAdhesionType.php'" class="letter_button">+</button>
<div class="padded">
<p><input type="button" onclick="location.href='main.php';" value="Retour" /></p> 
</div>
</div> 
</body>
</html>

