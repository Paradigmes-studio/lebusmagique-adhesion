<?php
require_once("db/mAdhesionClient.php");
require_once("lib/BrevoHandler.php");
require_once("init.php");

$domain_key = $domain . 'edit_adhesion_id';

$token = trim($_GET['token'] ?? '');
$error_message = htmlspecialchars($_GET['error'] ?? '');

if ($token !== '') {
	$m = new mAdhesionClient($conn, $conf);
	$adhesion = new AdhesionClient();
	if (!$m->find_by_token($token, $adhesion)) {
		http_response_code(404);
		$adhesion = null;
	} else {
		$_SESSION[$domain_key] = (int)$adhesion->id;
	}
} else {
	if (!empty($_SESSION[$domain_key])) {
		$m = new mAdhesionClient($conn, $conf);
		$adhesion = new AdhesionClient();
		if (!$m->read((int)$_SESSION[$domain_key], $adhesion)) {
			unset($_SESSION[$domain_key]);
			$adhesion = null;
		}
	} else {
		http_response_code(404);
		$adhesion = null;
	}
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

<?php if ($adhesion === null): ?>
	<div class="page-header">
		<img src="res/logo.png" alt="Le Bus Magique" class="dashboard-logo">
	</div>
	<div class="page-title" style="text-align:center;border:none;">Lien invalide</div>
	<p style="text-align:center;">Ton lien d'édition n'est pas valide ou a expiré. Contacte-nous au bar !</p>
<?php else: ?>
	<div class="page-header">
		<img src="res/logo.png" alt="Le Bus Magique" class="dashboard-logo">
	</div>
	<div class="page-title" style="text-align:center;border:none;">Ta fiche d'adhérent</div>

	<?php if ($error_message !== ''): ?>
		<div class="alert alert--error"><?php echo $error_message; ?></div>
	<?php endif; ?>

	<form action="mEditAdhesion.php" onsubmit="submit.disabled = true" method="POST">

		<div class="form-section">
			<label class="form-label">Nom</label>
			<div style="padding: 10px; background: #f5f5f5; border-radius: 8px;"><?php echo htmlspecialchars($adhesion->first_name . ' ' . $adhesion->last_name); ?></div>
		</div>

		<div class="form-section">
			<label class="form-label">Email</label>
			<input type="email" maxlength="200" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? $adhesion->email); ?>" placeholder="Email" required/>
		</div>

		<?php
			$is_subscribed = ((int)$adhesion->newsletter === 1);
			if (!empty($conf['brevoListIdNewsletter']) && !empty($adhesion->email)) {
				$brevo = new BrevoHandler($conn, $conf);
				if ($brevo->isConfigured()) {
					$brevo_state = $brevo->isInList($adhesion->email, (int)$conf['brevoListIdNewsletter']);
					if ($brevo_state !== null) {
						$is_subscribed = $brevo_state;
					}
				}
			}
		?>
		<div class="form-toggle">
			<span>Recevoir la prog et les nouvelles du bus</span>
			<label class="switch"><input name="subscribe" type="checkbox" <?php echo $is_subscribed ? 'checked' : ''; ?>><span class="slider round"></span></label>
		</div>

		<div class="form-section">
			<label class="form-label">Type d'adhésion</label>
			<div style="padding: 10px; background: #f5f5f5; border-radius: 8px;"><?php echo htmlspecialchars($adhesion->adhesion_type); ?></div>
		</div>

		<div class="form-section">
			<label class="form-label">Adhésion actuelle</label>
			<div style="padding: 10px; background: #f5f5f5; border-radius: 8px;">
				Du <?php echo date('d/m/Y', strtotime($adhesion->date_debut)); ?>
				au <?php echo date('d/m/Y', strtotime($adhesion->date_fin)); ?>
			</div>
		</div>

		<?php
			$end_date = date('Y-m-d', strtotime($adhesion->date_fin));
			$still_valid = $end_date > date('Y-m-d');
		?>
		<?php if ($still_valid): ?>
			<div class="form-section">
				<div style="padding: 10px; background: #f5f5f5; border-radius: 8px;">
					Ton adhésion est encore valide jusqu'au <?php echo date('d/m/Y', strtotime($adhesion->date_fin)); ?>. Tu pourras la renouveler à partir de cette date.
				</div>
			</div>
		<?php else: ?>
			<input type="hidden" name="renew" value="on">
		<?php endif; ?>

		<div class="form-section">
			<input type="text" maxlength="200" name="code_valid" value="" placeholder="Code de validation (demande au bar !)" required/>
		</div>

		<p><input type="submit" id="submit" value="<?php echo $still_valid ? 'Enregistrer' : 'Renouveler'; ?>"/></p>
	</form>
<?php endif; ?>

</div>
</body>
</html>
