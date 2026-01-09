<?php
require_once("db/mAdhesionType.php");
//require_once("lib/User.php");
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

$err=""; 
if ($_POST['name'] == '') {
	$err = "Name required";
}

if ($err != '') {
	header(sprintf('Location: editAdhesionType.php?error=%s', $err)); 
}

$edited_adhesion_type = new AdhesionType();
$t = new mAdhesionType($conn, $conf);

if (!$_POST['new']) {
	if (!$t->read($_POST['id'], $edited_adhesion_type)) {
		header('Location: editAdhesionType.php?error=Adhesion not found');
		exit;
	}
} 

/*Gestion des erreurs sur la form de création d'adhésion*/
$err = "";
$values= "";

if ($_POST['name'] == "")
	$err .= "nameErr=Il faut un nom!&";
else {
	$edited_adhesion_type->name = $_POST['name'];
	$values .= "name=" . $_POST['name'] . "&";
}

if ($_POST['price'] == "")
	$edited_adhesion_type->price = NULL;
else {
	$edited_adhesion_type->price = floatval($_POST['price']);
	$values .= "price=" . $_POST['price'] . "&";
}

if ($_POST['email_welcome'] == "")
	$err .= "emailErr=Il faut un modele d'email!&";
else {
	$edited_adhesion_type->email_welcome = $_POST['email_welcome'];
	$values .= "email_welcome=" . $_POST['email_welcome'] . "&";
}
if ($_POST['duration'] == "")
	$err .= "durationErr=Il faut une durée de validité!&";	
else {
	$edited_adhesion_type->duration = (int)$_POST['duration'];
	$values .= "duration=" . $_POST['duration'] . "&";
} 

if ($err != "") {
	header(sprintf('Location: editAdhesionType.php?%s%s', $err, substr_replace($values ,"",-1))); 
	exit;
}


//$edited_adhesion_type->widget_descriptions['en'] = $_POST['widget_description_en'];
//$edited_adhesion_type->widget_descriptions['fr'] = $_POST['widget_description_fr'];

$t->write($edited_adhesion_type); 


header(sprintf('Location: listAdhesionType.php'));
?>
