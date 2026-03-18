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
		header('Location: login.php?error=Session expirée');
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
</head>
<body class="defaultback">
<div class="page">

<?php if ($new): ?>
<div class="page-header">
	<img src="res/logo.png" alt="Le Bus Magique" class="dashboard-logo">
</div>
<div class="page-title" style="text-align:center;border:none;">T'embarques avec nous?</div>
<?php else: ?>
<div class="page-title">Adhésion #<?php echo $adhesion_client->id; ?></div>
<?php printf('<input type="hidden" name="id" value="%d">', $adhesion_client->id); ?>
<?php endif; ?>

<form action="mCreateAdhesionClient.php" onsubmit="submit.disabled = true" method="POST">

<?php
	$a = new mAdhesionType($conn, $conf);
	$nb_type = $a->get_count();
	$adhesions_type = $a->list_adhesion_type();

	if ($new) {
		$adhesion_type_err = isset($_GET['typeAdhesionErr']);
		if ($adhesion_type_err)
			printf('<div class="alert alert--error">%s</div>', htmlspecialchars($_GET['typeAdhesionErr']));

		if ($nb_type > 1) {
			print('<label class="form-label">Type d\'adhesion</label>');
			printf('<div class="option-group %s">', $adhesion_type_err ? 'option-group--error' : '');
			foreach($adhesions_type as $adhesion_type) {
				$checked = (((isset($_GET['adhesionType'])) && ($_GET['adhesionType'] == $adhesion_type->id)) || ($nb_type == 1)) ? 'checked' : '';
				$label = ($adhesion_type->price == null || $adhesion_type->price == 0) ? $adhesion_type->name : sprintf("%s - %.2f&euro;", $adhesion_type->name, $adhesion_type->price);
				printf('<label class="option-item"><input type="radio" name="adhesion_type" value="%d" %s><span>%s</span></label>', $adhesion_type->id, $checked, $label);
			}
			print('</div>');
		} else {
			foreach($adhesions_type as $adhesion_type) {
				$label = ($adhesion_type->price == null || $adhesion_type->price == 0) ? $adhesion_type->name : sprintf("%s - %.2f&euro;", $adhesion_type->name, $adhesion_type->price);
				printf('<div class="option-group"><label class="option-item"><input type="radio" name="adhesion_type" value="%d" checked><span>%s</span></label></div>', $adhesion_type->id, $label);
			}
		}
	} else {
		print('<label class="form-label">Type d\'adhesion</label>');
		$adhesion_type_err = isset($_GET['typeAdhesionErr']);
		if ($adhesion_type_err)
			printf('<div class="alert alert--error">%s</div>', htmlspecialchars($_GET['typeAdhesionErr']));
		if (!isset($_GET['lastName']))
			printf('<input type="text" maxlength="200" name="adhesion_type" value="%s" placeholder="Type d\'adhesion"/>', htmlspecialchars($adhesion_client->adhesion_type));
		else
			printf('<input type="text" maxlength="200" name="adhesion_type" value="%s" placeholder="Type d\'adhesion"/>', htmlspecialchars($_GET['typeAdhesion'] ?? ''));
	}
?>

<div class="form-section">
<?php
	// Last name
	$last_name_err = isset($_GET['lastNameErr']);
	if ($last_name_err)
		printf('<div class="alert alert--error">%s</div>', htmlspecialchars($_GET['lastNameErr']));
	$ln_val = $last_name_err ? htmlspecialchars($_GET['lastName']) : ((!$new && !isset($_GET['lastName'])) ? htmlspecialchars($adhesion_client->last_name) : htmlspecialchars($_GET['lastName'] ?? ''));
	printf('<input type="text" maxlength="200" name="last_name" value="%s" placeholder="Nom" %s/>', $ln_val, $last_name_err ? 'class="FieldError"' : '');

	// First name
	$first_name_err = isset($_GET['firstNameErr']);
	if ($first_name_err)
		printf('<div class="alert alert--error">%s</div>', htmlspecialchars($_GET['firstNameErr']));
	$fn_val = $first_name_err ? htmlspecialchars($_GET['firstName']) : ((!$new && !isset($_GET['firstName'])) ? htmlspecialchars($adhesion_client->first_name) : htmlspecialchars($_GET['firstName'] ?? ''));
	printf('<input type="text" maxlength="200" name="first_name" value="%s" placeholder="Prénom" %s/>', $fn_val, $first_name_err ? 'class="FieldError"' : '');

	// Email
	$email_err = isset($_GET['emailErr']);
	if ($email_err)
		printf('<div class="alert alert--error">%s</div>', htmlspecialchars($_GET['emailErr']));
	$em_val = $email_err ? htmlspecialchars($_GET['email']) : ((!$new && !isset($_GET['email'])) ? htmlspecialchars($adhesion_client->email) : htmlspecialchars($_GET['email'] ?? ''));
	printf('<input type="email" maxlength="200" name="email" value="%s" placeholder="Email" %s/>', $em_val, $email_err ? 'class="FieldError"' : '');
?>
</div>

<div class="form-toggle">
	<span>Recevoir la prog et les nouvelles du bus</span>
	<?php
		$checked = ((!$new && $adhesion_client->newsletter == 1) || ($new && ($_GET['subscribe'] ?? '') == "on") || ($new && !isset($_GET['subscribe']))) ? "checked" : "";
	?>
	<label class="switch"><input name="subscribe" type="checkbox" <?php echo $checked; ?>><span class="slider round"></span></label>
</div>

<?php if ($new): ?>
<div class="form-section">
	<?php
		if (isset($_GET['codeValidErr']))
			printf('<div class="alert alert--error">%s</div>', htmlspecialchars($_GET['codeValidErr']));
		$cv_class = isset($_GET['codeValidErr']) ? 'class="FieldError"' : '';
		printf('<input type="text" maxlength="200" name="code_valid" value="%s" placeholder="Code de validation (demande au bar!)" %s/>', htmlspecialchars($_GET['codeValid'] ?? ''), $cv_class);
	?>
</div>
<p><input type="submit" id="submit" value="Adhérer"/></p>
<?php else: ?>

<div class="form-section">
	<label class="form-label">Dates d'adhesion</label>
	<div class="form-row">
		<div class="form-row-item">
			<label class="form-label">Debut</label>
			<input type="date" name="date_debut" value="<?php echo date('Y-m-d', strtotime($adhesion_client->date_debut)); ?>" required/>
		</div>
		<div class="form-row-item">
			<label class="form-label">Fin</label>
			<input type="date" name="date_fin" value="<?php echo date('Y-m-d', strtotime($adhesion_client->date_fin)); ?>" required/>
		</div>
	</div>
</div>

<div class="form-section">
	<label class="form-label">Carte d'adhesion</label>
	<?php printf('<img src="res/Carte%d.jpg?rand=%d" alt="Aucune carte trouvee" class="card-preview"/>', $adhesion_client->id, mt_rand(0, 0xffff)); ?>
</div>

<div class="form-section">
	<label class="form-label">Actions</label>
	<div class="action-list">
		<div class="form-toggle">
			<span>Régénérer la carte</span>
			<label class="switch"><input name="carte" type="checkbox"><span class="slider round"></span></label>
		</div>
		<div class="form-toggle">
			<span>Envoyer un email</span>
			<label class="switch"><input name="sendmail" type="checkbox"><span class="slider round"></span></label>
		</div>
		<?php
			$e = new EmailHandler($conn, $conf);
			$models = $e->get_models();
		?>
		<label class="form-label">Modèle d'email</label>
		<select name="email_resend">
		<?php foreach($models as $model): ?>
			<option value="<?php echo htmlspecialchars($model); ?>"><?php echo htmlspecialchars($model); ?></option>
		<?php endforeach; ?>
		</select>
	</div>
</div>
<p><input type="submit" id="submit" value="Enregistrer"/></p>
<?php endif; ?>

<?php if ($user != NULL): ?>
	<p><a href="<?php echo $new ? 'main.php' : 'searchAdhesionClient.php'; ?>" class="page-back">Retour</a></p>
<?php endif; ?>

</form>
</div>
</body>
</html>
