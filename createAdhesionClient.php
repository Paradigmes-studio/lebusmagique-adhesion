<?php
require_once("db/mAdhesionClient.php");
require_once("db/mAdhesionType.php");
require_once("lib/EmailHandler.php");
require_once("init.php");
//require_once("get_login_info.php"); // if not, redirect

$adhesion_client = new AdhesionClient;

$new = !isset($_GET['id']);

$user=NULL;
if (isset($_SESSION)) {
	if (isset($_SESSION[$domain.'user'])) {
		$user=unserialize($_SESSION[$domain.'user']);
	}
}

if (!$new) {
	if ($user==NULL) {
		header('Location: login.php?error=Disconnected');
		exit();
	}
	$t = new mAdhesionClient($conn, $conf);
	$t->read($_GET['id'], $adhesion_client); 
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
<form action="mCreateAdhesionClient.php" onsubmit="submit.disabled = true" method="POST">
<?php

	if ($new) {
		print('</br><text class="title">T\'embarques avec nous?</text><br/><br/><br/>');	
	} else {
		printf('</br><text class="title">Modification de l\'adhésion n°%d</text><br/><br/><br/>', $adhesion_client->id);	
		printf('<input type="hidden" name="id" value="%d">', $adhesion_client->id);
	}


	$a = new mAdhesionType($conn, $conf);
	$nb_type = $a->get_count();
	$adhesions_type = $a->list_adhesion_type();
	if ($new) {
		if ($nb_type == 1)
			print("Type d'adhésion<br/>");		
		else
			print("Choisis ton type d'adhésion<br/>");	
		$adhesion_type_err = isset($_GET['typeAdhesionErr']);
		if ($adhesion_type_err) {
			printf('<text class = "TextError">%s</text><br/>', $_GET['typeAdhesionErr']);
			printf('<div class="FieldError" name="adhesion_type" class="option">');
		} else
			printf('<div name="adhesion_type" class="option">');

		foreach($adhesions_type as $adhesion_type) {
			if (((isset($_GET['adhesionType'])) && ($_GET['adhesionType'] == $adhesion_type->id)) || ($nb_type == 1))
				$checked = 'checked="checked"';
			else
				$checked = '';

			if ($adhesion_type->price == null)
				$label = $adhesion_type->name;
			else
				$label = sprintf("%s - %.2f€",$adhesion_type->name, $adhesion_type->price);
		
			printf('<label><input type="radio" name="adhesion_type" id="%d" value="%d" %s>%s<label></br>', $adhesion_type->id, $adhesion_type->id, $checked, $label);
			printf ('</div>');
		}
	} else {
		print("Type d'adhésion<br>");	
		$adhesion_type_err = isset($_GET['typeAdhesionErr']);
		if ($adhesion_type_err) {
			printf('<text class = "TextError">%s</text><br/>', $_GET['typeAdhesionErr']);
			printf('<input type="text" class="FieldError" maxlength="200" name="adhesion_type" value="%s" placeholder="Type d\'adhésion"/>', $_GET['typeAdhesion']); 
		} else
			if (!isset($_GET['lastName']))
				printf('<input type="text" maxlength="200" name="adhesion_type" value="%s" placeholder="Type d\'adhésion"/>', $adhesion_client->adhesion_type); 
			else
				printf('<input type="text" maxlength="200" name="adhesion_type" value="%s" placeholder="Type d\'adhésion"/>', $_GET['typeAdhesion']); 
		
	}
	
	//print('Nom<br/>');
	print("<br><br>Infos adhérent<br>");	
	$last_name_err = isset($_GET['lastNameErr']);
	if ($last_name_err) {
		printf('<text class = "TextError">%s</text><br/>', $_GET['lastNameErr']);
		printf('<input type="text" class="FieldError" maxlength="200" name="last_name" value="%s" placeholder="Nom"/>', $_GET['lastName']); 
	} else
		if ((!$new) && (!isset($_GET['lastName'])))
			printf('<input type="text" maxlength="200" name="last_name" value="%s" placeholder="Nom"/>', $adhesion_client->last_name); 
		else
			printf('<input type="text" maxlength="200" name="last_name" value="%s" placeholder="Nom"/>', $_GET['lastName']); 

	//print('Prénom<br/>');
	$first_name_err = isset($_GET['firstNameErr']);
	if ($first_name_err) {
		printf('<text class = "TextError">%s</text><br/>', $_GET['firstNameErr']);
		printf('<input type="text" class="FieldError" maxlength="200" name="first_name" value="%s" placeholder="Prénom"/>', $_GET['firstName']); 
	} else
		if ((!$new) && (!isset($_GET['firstName'])))
			printf('<input type="text" maxlength="200" name="first_name" value="%s" placeholder="Prénom"/>', $adhesion_client->first_name); 
		else
			printf('<input type="text" maxlength="200" name="first_name" value="%s" placeholder="Prénom"/>', $_GET['firstName']); 

	//print('Email<br/>');		
	$email_err = isset($_GET['emailErr']);
	if ($email_err) {
		printf('<text class = "TextError">%s</text><br/>', $_GET['emailErr']);
		printf('<input type="text" class="FieldError" maxlength="200" name="email" value="%s" placeholder="Email"/>', $_GET['email']); 
	} else
		if ((!$new) && (!isset($_GET['email'])))
			printf('<input type="text" maxlength="200" name="email" value="%s" placeholder="Email"/>', $adhesion_client->email); 
		else
			printf('<input type="text" maxlength="200" name="email" value="%s" placeholder="Email"/>', $_GET['email']); 

	//recevoir newsletter
	if (((!$new) && ($adhesion_client->newsletter == 1)) || (($new) && ($_GET['subscribe'] == "on")) || (($new) && (!isset($_GET['subscribe']))))
		$checked = "unchecked";
	else
		$checked = "";
	printf('<br><br><div class="text">Recevoir la prog et les nouvelles du bus</br></div><div class="tabu">
	<label class="switch"><input name="subscribe" type="checkbox" %s><span class="slider round"></span></label>
	</div><br>', $checked);



	//print('Code (à demander au bar!)<br/>');		
	if ($new) {
		if (isset($_GET['codeValidErr'])) {
			printf('<text class = "TextError">%s</text><br/>', $_GET['codeValidErr']);
			printf('<input type="text" class="FieldError" maxlength="200" name="code_valid" value="%s" placeholder="Code de validation"/>', $_GET['codeValid']); 
		} else
			printf('<input type="text" maxlength="200" name="code_valid" value="%s" placeholder="Code de validation"/>', $_GET['codeValid']); 
		
		printf('<p><input type="submit" id="submit" value="Adhérer"/></p>');
	} else {
		//date d'adhésion
		print("<br>Dates d'adhésion<br>");	
		printf('<label for="date_debut">Début : </label><input type="date" name="date_debut" value="%s" style="width:auto" required/><br>', date('Y-m-d', strtotime($adhesion_client->date_debut)));
		printf('<label for="date_fin">Fin : </label><input type="date" name="date_fin" value="%s" style="width:auto" required/><br>', date('Y-m-d', strtotime($adhesion_client->date_fin)));

		//Carte de membre
		print("<br><br>Carte d'adhésion<br>");		
		printf('<img src="res/Carte%d.jpg?rand=%d" alt="Aucune carte trouvée" style="width:100%%;border:solid;align:center"/><br>', $adhesion_client->id, mt_rand(0, 0xffff));
		
		//Actions
		print("<br><fieldset>");
		print("<legend>Actions</legend>"); 
			printf('<div class="text">Enregistrer les modifications</br></div><div class="tabu">
				<label class="switch"><input name="data" type="checkbox" checked disabled><span class="slider round"></span></label>
				</div>');
			printf('<div class="text">Regénérer la carte d\'adhérent</br></div><div class="tabu">
				<label class="switch"><input name="carte" type="checkbox"><span class="slider round"></span></label>
				</div>');
				// Recherche des modeles d'email disponible dans le dossier des ressources externes
				print('Model d\'e-mail à envoyer<br/>');
				$e=new EmailHandler($conn, $conf);
				print('<select name="email_resend">');
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
			printf('<div class="text">Envoyer l\'email séléctionné</br></div><div class="tabu">
				<label class="switch"><input name="sendmail" type="checkbox"><span class="slider round"></span></label>
				</div>');
			printf('<p><input type="submit" id="submit" value="Executer les actions"/></p>');
		print("</fieldset>");
		
	}

	if ($user!=NULL) {
		if ($new)
			print('<p><input type="button" onclick="location.href=\'main.php\';" value="Retour" /></p>'); 
		else
			print('<p><input type="button" onclick="location.href=\'searchAdhesionClient.php\';" value="Retour" /></p>'); 
	}
?>
</form>

</div>
</body>
<script>
exceptionsArrayToTable();
</script>
</html>
