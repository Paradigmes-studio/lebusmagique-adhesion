<?php
ob_start();
require_once("db/mAdhesionClient.php");
require_once("db/mAdhesionType.php");
require_once("lib/EmailHandler.php");
require_once("lib/CreateImageText.php");
require_once("lib/BrevoHandler.php");
require_once("lib/AdhesionEdit.php");
require_once("init.php");
require_once("config.php");
//require_once("get_login_info.php"); // if not, redirect

//Si id en parameter, on est en modification
$new = !isset($_POST['id']);


/********************************************************/
/*Gestion des erreurs sur la form de création d'adhésion*/
/********************************************************/
$err = "";
$values= "";
if ($_POST['last_name'] == "") {
	$err .= "lastNameErr=Il nous manque ton nom!&";
} else
	$values .= "lastName=" . $_POST['last_name'] . "&";

if ($_POST['first_name'] == "") {
	$err .= "firstNameErr=Il nous manque ton prénom!&";
} else
	$values .= "firstName=" . $_POST['first_name'] . "&";


//Test de l'email avec test dns et syntaxe de l'adresse
//
$email = preg_replace('/\s+/', '', $_POST['email']);
if ($email == "") {
	$err .= "emailErr=Il nous manque ton email!&";
} else {
	$values .= "email=" . $email . "&";
	list($addressmail, $domain) = explode('@', $email);
	if (filter_var($email, FILTER_VALIDATE_EMAIL) == false)
		$err .= "emailErr=Il y a une erreur sur ton email!&";
	else
    	if (!checkdnsrr($domain, "MX"))
			$err .= "emailErr=Email non valide!&";
}

//Test du type d'adhésion
//
if ($_POST['adhesion_type'] == "") {
	$err .= "typeAdhesionErr=Quel type d'adhésion veux-tu?&";
} else
	$values .= "adhesionType=" . $_POST['adhesion_type'] . "&";

$values .= "subscribe=" . ($_POST['subscribe'] ?? '') . "&";


//Test du code de validation si nouvelle adhésion
//
if ($new) {	
	$codeValid = $_POST['code_valid'] ?? '';
	if ($codeValid == "") {
		$err .= "codeValidErr=Demande un code au bar&";
	} else {
		$values .= "codeValid=" . $codeValid . "&";
		$day = date('d', time());
		$month = date('m', time());
		if ((strlen($codeValid) != 4) || (strrev(substr($codeValid, 0, 2)) != $day) || (strrev(substr($codeValid, 2, 2)) != $month))
			$err .= "codeValidErr=Code erroné... Désolé&";
	}
} else {
	$values .= "id=" . $_POST['id'] . "&";
}

if ($new) {
	$referral = $_POST['referral_source'] ?? '';
	if ($referral == "") {
		$err .= "referralErr=Dis-nous comment tu nous as connus !&";
	} else {
		$values .= "referralSource=" . $referral . "&";
		if ($referral == "autre") {
			$referral_other = $_POST['referral_other'] ?? '';
			if ($referral_other == "") {
				$err .= "referralErr=Précise comment tu nous as connus !&";
			} else {
				$values .= "referralOther=" . $referral_other . "&";
			}
		}
	}
}

//!!!!!!! tester erreurs sur date if not new
//
//
//

// Si erreur détectée, on retourne à la form avec les valeurs en paramètres
if ($err != "") {
	header(sprintf('Location: createAdhesionClient.php?%s%s', $err, substr_replace($values ,"",-1))); 
	exit;
}

/**********************************************/
/*Création de l'enregistrement depuis la form */
/**********************************************/
$edited_adhesion_client = new AdhesionClient();
$t = new mAdhesionClient($conn, $conf);

$edited_adhesion_client->last_name = $_POST['last_name'];
$edited_adhesion_client->first_name = $_POST['first_name'];
$edited_adhesion_client->email = preg_replace('/\s+/', '', $_POST['email']);

if ($new) {
	$a = new mAdhesionType($conn, $conf);
	$adhesion_type = new AdhesionType;
	$a->read($_POST['adhesion_type'], $adhesion_type);
	$edited_adhesion_client->adhesion_type = $adhesion_type->name;
} else {
	$edited_adhesion_client->adhesion_type = $_POST['adhesion_type'];
}

if ($new) {
	$edited_adhesion_client->date_debut = date('Y-m-d H:i:s', time());
	$edited_adhesion_client->date_fin = date('Y-m-d H:i:s', strtotime('+ ' . $adhesion_type->duration . ' days'));
} else {
	$edited_adhesion_client->date_debut = date('Y-m-d H:i:s', strtotime($_POST['date_debut']));
	$edited_adhesion_client->date_fin = date('Y-m-d H:i:s', strtotime($_POST['date_fin']));
}
if (($_POST['subscribe'] ?? '') == "on")
	$edited_adhesion_client->newsletter = true;
else
	$edited_adhesion_client->newsletter = false;

$old_date_fin = null;
if ($new) {
	$referral = $_POST['referral_source'];
	if ($referral === 'autre') {
		$edited_adhesion_client->referral_source = 'autre:' . $_POST['referral_other'];
	} else {
		$edited_adhesion_client->referral_source = $referral;
	}
} else {
	$existing = new AdhesionClient();
	$t->read($_POST['id'], $existing);
	$edited_adhesion_client->referral_source = $existing->referral_source;
	$old_date_fin = $existing->date_fin;
}

if (!$new) {
	$edited_adhesion_client->id = $_POST['id'];
	$edited_adhesion_client->new = false;
}

//ecriture en base de l'enregistrement
//
$t->write($edited_adhesion_client); 

$erreur = "";
$result = true;

$date_fin_changed = !$new && $old_date_fin !== $edited_adhesion_client->date_fin;
$manual_carte = !$new && ($_POST['carte'] ?? '') == "on";
$manual_sendmail = !$new && ($_POST['sendmail'] ?? '') == "on";
$should_regen_card = $new || $manual_carte || $date_fin_changed;
$should_send_email = $new || $manual_sendmail || $date_fin_changed;

/********************************/
/*Edition de la carte d'adhérent*/
/********************************/
if ($should_regen_card) {
	if (!regenerate_adhesion_card($edited_adhesion_client)) {
		$result = false;
		$erreur = "Erreur à la génération de la carte adhérent";
	}
}

/*******************************/
/*Envoi de l'email de bienvenue*/
/*******************************/
if ($result && $should_send_email && $conf["send_email"]) {
	if ($manual_sendmail) {
		$model = $_POST['email_resend'];
	} elseif ($new) {
		$model = $adhesion_type->email_welcome;
	} else {
		$autoType = new AdhesionType();
		try {
			$a2 = new mAdhesionType($conn, $conf);
			$a2->read_by_name($edited_adhesion_client->adhesion_type, $autoType);
			$model = $autoType->email_welcome;
		} catch (Exception $e) {
			$model = null;
			error_log(sprintf("Adhesion type lookup failed for auto renewal email: %s", $e->getMessage()));
		}
	}
	if (!empty($model)) {
		$mailRes = send_adhesion_welcome_email($edited_adhesion_client, $model, $conn, $conf);
		if (!$mailRes['ok']) {
			$result = false;
			$erreur = "Erreur à l'envoi de l'email de bienvenue";
			error_log(sprintf("Error while sending booking email: %s - model : %s", $mailRes['error'] ?? '?', $model));
		}
	}
}

/**********************************************************/
/*Synchronisation Brevo : liste Adhésion + Newsletter opt */
/**********************************************************/
$brevo = new BrevoHandler($conn, $conf);
if ($brevo->isConfigured()) {
	$listIds = [];
	$unlinkListIds = [];
	if (!empty($conf['brevoListIdAdhesion'])) {
		$listIds[] = (int)$conf['brevoListIdAdhesion'];
	}
	$subscribed = (($_POST['subscribe'] ?? '') == "on");
	if ($subscribed && !empty($conf['brevoListIdNewsletter'])) {
		$listIds[] = (int)$conf['brevoListIdNewsletter'];
	} elseif (!$subscribed && !$new && !empty($conf['brevoListIdNewsletter'])) {
		$unlinkListIds[] = (int)$conf['brevoListIdNewsletter'];
	}
	$res = $brevo->upsertContact($edited_adhesion_client, $listIds, $unlinkListIds);
	if (!$res['ok']) {
		error_log(sprintf("Brevo contact sync failed: HTTP %s - %s", $res['http'] ?? '?', $res['body'] ?? $res['msg'] ?? ''));
	}
}

/************************/
/*Message de féliciation*/
/************************/
$nextPage = "createAdhesionClient.php";
$buttonName = "Nouvelle adhésion";

if ($result) {
	if ($new) {
		$title = "Bienvenue!";
		$text = "Ton numéro d'adhérent est : ". $edited_adhesion_client->id ."<br>Ta carte d'adhésion a été envoyée sur " . $edited_adhesion_client->email . ".<br>Si tu ne la vois pas, vérifie tes spams.<br>Et maintenant tu n'as plus qu'à profiter !";
	} else {
		$title = "Ca à marché!";
		$text = "L'adhésion n°". $edited_adhesion_client->id . " a bien été modifiée";
		$nextPage = 'createAdhesionClient.php?id=' . $edited_adhesion_client->id;
		$buttonName = "Retour";
	}
} else {
	$title = "Erreur :(";
	$text = $erreur . " - Vois avec un serveur comment faire... Désolé...";
}
//header(sprintf('Location: message.php?title=%s&text=%s&nextPage=%s&buttonName=%s&image=%s',$title,$text,$nextPage,$buttonName,'res%2FCarte'. $edited_adhesion_client->id .'.jpg'));
header(sprintf('Location: message.php?title=%s&text=%s&nextPage=%s&buttonName=%s&adhesionId=%s',$title,$text,$nextPage,$buttonName, $edited_adhesion_client->id));

?>
