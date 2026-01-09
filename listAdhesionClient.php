<?php
require_once("db/mAdhesionClient.php");
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
<body class="defaultback"> 
<div class="main">
</head>
<body class="defaultback"> 
<div class="main">
<div class="list" style="width: 100%; max-width:100%">
<table style="table-layout: fixed;width: 100%">
<tr><th style="width:90%">Adhesions</th><th style="width:10%">Act</th></tr>
<?php

	//récupération des critères de recherche pour construire clause where du select
	$where = "";
	$orderby = "";
	if ($_POST['last_name'] != "") {
		$where .= 'soundex(last_name)=soundex("' . $_POST['last_name'] . '") and ';
		$orderby = " order by strcmp(last_name, '" . $_POST['last_name'] . "') desc, last_name";
	}
	if ($_POST['first_name'] != "") {
		$where .= 'soundex(first_name)=soundex("' . $_POST['first_name'] . '") and ';
		$orderby = " order by strcmp(first_name, '" . $_POST['first_name'] . "') desc, first_name";
	}
	if ($_POST['email'] != "")
		$where .= 'email="' . $_POST['email'] . '" and ';
	if ($_POST['adherent_id'] != "")
		$where .= 'id="' . $_POST['adherent_id'] . '" and ';

	if ($where != "") {
		$where = "where " . substr_replace($where,"",-4);
		$where = $where . $orderby;
	}
	
	$ac = new mAdhesionClient($conn, $conf);
	
	try {
		$adhesions = $ac->search($where);
	} catch (\Exception $e) {
		print($e->getMessage());
	}
	
	//Remplissage du tableau
	$bg_color = "white";
	foreach($adhesions as $adhesion) {
		if (date_format(new DateTime($adhesion->date_fin), 'Y-m-d') < date('Y-m-d', time()))
			if ($bg_color == "white")
				$bg_color = "#ff6969";
			else
				$bg_color = "red";

		print("<tr>");
		printf("<td style='overflow:hidden; width:100%%;background-color: ". $bg_color .";'>N° adhérent : %s - %s %s</br>
			%s - %s</br>
			E-mail : %s
			</td>", $adhesion->id, $adhesion->first_name, $adhesion->last_name,$adhesion->adhesion_type, date_format(new DateTime($adhesion->date_fin), 'd/m/Y'),  $adhesion->email);

		printf("<td style=\"text-align:center;background-color: ". $bg_color .";\">");
		printf("<button type=\"button\" onclick=\"confirm_action('Etes vous sûr de vouloir supprimer l\'adhérent n°%s?', 'mDeleteAdhesionClient.php?id=%s')\"
			 class=\"letter_button_red\">X</button>\n", $adhesion->id, $adhesion->id);
		printf("</br><button onclick=\"location='createAdhesionClient.php?id=%d'\" class=\"letter_button\">M</button>\n", $adhesion->id);
		printf("</td>\n");
		printf("</tr>\n");
		if (($bg_color == "white") || ($bg_color == "#ff6969"))
			$bg_color = "#fff7d5";
		else
			$bg_color = "white";
	}

?> 
</table>
</table>
<div class="padded">
<p><input type="button" onclick="location.href='searchAdhesionClient.php';" value="Retour" /></p> 
</div> 
</div>
</body>
</html>
