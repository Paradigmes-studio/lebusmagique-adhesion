<?php
require_once("lib/EmailHandler.php");
require_once("db/mAdhesionType.php");
//require_once("lib/User.php");
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

$adhesion_type = new AdhesionType;
$new = !isset($_GET['id']);
if (!$new) {
	$t = new mAdhesionType($conn, $conf);
	$t->read($_GET['id'], $adhesion_type); 
} 

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
<form action="mEditAdhesionType.php" onsubmit="exceptionsTableToArray()" method="POST">
<?php
	if ($new) {
	} else {
		printf('<input type="hidden" name="id" value="%d">', $adhesion_type->id);
	}
	
	
	printf('<input type="hidden" name="new" value="%d">', $new);

	print('</br><text class="title">Type d\'adhésion</text><br/><br/><br/>');	


	// Nom du type d'adhésion
	if (isset($_GET['nameErr'])) {
		printf('<text class = "TextError">%s</text><br/>', $_GET['nameErr']);
		printf('<input type="text" class="FieldError" maxlength="200" name="name" value="%s" placeholder="Nom de l\'adhesion"/>', $_GET['name']);
	} else
		if (isset($_GET['name'])) 
			printf('<input type="text" maxlength="200" name="name" value="%s" placeholder="Nom de l\'adhesion"/>', $_GET['name']);
		else
			printf('<input type="text" maxlength="200" name="name" value="%s" placeholder="Nom de l\'adhesion"/>', $adhesion_type->name);  
	// Prix de l'd'adhésion (Si null, il n'est pas affiché -> prix libre)
	print('Prix<br/>');
	if (isset($_GET['price']))
		printf('<input type="number" name="price" min="0" step="0.01" value="%.2f" placeholder="Prix de l\'adhésion"/>', $_GET['price']);
	else
		printf('<input type="number" name="price" min="0" step="0.01" value="%.2f" placeholder="Prix de l\'adhésion"/>', $adhesion_type->price);

	// Recherche des modeles d'email disponible dans le dossier des ressources externes
	print('Model e-mail de bienvenue<br/>');
	$e=new EmailHandler($conn, $conf);
	print('<select name="email_welcome">');
	$models=$e->get_models(); 
	$no_selected="";
	$no_model=""; 
	if ($adhesion_type->new) {
		$no_selected="selected";
	} else {
		if ($adhesion_type->email_welcome=="") {
			$no_model="selected";
		}
	} 
	foreach($models as $model) {
		$s="";
		if ($adhesion_type->email_welcome != "") {
			if ($model==$adhesion_type->email_welcome) {
				$s="selected";
			}
		}
		printf('<option value="%s" %s>%s</option>', $model, $s, $model);
	} 
	print("</select>");


	if (isset($_GET['durationErr'])) {
		printf('<text class = "TextError">%s</text><br/>', $_GET['durationErr']);
		printf('<input type="number" class="FieldError" name="duration" min="0" max="720" step="1" value="%s" placeholder="Durée de validité en jours"/>',  $_GET['duration']);
	} else
		if (isset($_GET['duration'])) 
			printf('<input type="number" name="duration" min="0" max="720" step="1" value="%s" placeholder="Durée de validité en jours"/>',  $_GET['duration']);
		else
			printf('<input type="number" name="duration" min="0" max="720" step="1" value="%s" placeholder="Durée de validité en jours"/>', $adhesion_type->duration);

/*	print('English description<br/>');
	$widget_description_en = '';
	if (array_key_exists('en', $adhesion_type->widget_descriptions)) {
		$widget_description_en = $adhesion_type->widget_descriptions['en'];
	}
	$widget_description_fr = '';
	if (array_key_exists('fr', $adhesion_type->widget_descriptions)) {
		$widget_description_fr = $adhesion_type->widget_descriptions['fr'];
	}

	printf('<textarea name="widget_description_en" placeholder="Description for the widget" rows="5" >%s</textarea>', $widget_description_en); 
	print('French description<br/>');
	printf('<textarea name="widget_description_fr" placeholder="Description for the widget" rows="5" >%s</textarea>', $widget_description_fr); 
	*/

?>
<p><input type="submit" value="Enregistrer"/></p>
</form>
<p><input type="button" onclick="location='listAdhesionType.php';" value="Annuler" /></p> 
</div>
</body>
<script>
exceptionsArrayToTable();
</script>
</html>
