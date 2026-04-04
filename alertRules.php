<?php
require_once("db/mAlertRule.php");
require_once("db/mEmailTemplate.php");
require_once("init.php");
require_once("get_login_info.php");

$manager = new mAlertRule($conn, $conf);
$tplManager = new mEmailTemplate($conn, $conf);
$models = $tplManager->list_names();

$editing = isset($_GET['id']);
$alert_rule = new AlertRule();
if ($editing) {
	$manager->read($_GET['id'], $alert_rule);
}

if (isset($_GET['delete'])) {
	$manager->delete($_GET['delete']);
	header('Location: alertRules.php');
	exit;
}

if (isset($_GET['toggle'])) {
	$manager->toggle_active($_GET['toggle']);
	header('Location: alertRules.php');
	exit;
}

$rules = $manager->list_all();

$trigger_labels = [
	'before' => 'Avant expiration',
	'on' => "Jour d'expiration",
	'after' => 'Après expiration',
];
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
<div class="page page--wide">
<div class="page-title">Alertes de relance</div>

<div style="background: #fff3e0; border-radius: 16px; padding: 12px 16px; margin-bottom: 16px;">
	<div style="font-weight: 600;">Alertes désactivées</div>
	<div style="font-size: 0.85em; color: #666;">En attente de la migration vers Brevo. L'activation se fera après la mise en place du nouveau service d'envoi.</div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success"><?php echo htmlspecialchars($_GET['success']); ?></div>
<?php endif; ?>

<div style="background: white; border-radius: 16px; padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
	<div style="font-size: 0.8em; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
		<?php echo $editing ? 'Modifier l\'alerte' : 'Nouvelle alerte'; ?>
	</div>
	<form action="mAlertRules.php" method="POST">
		<?php if ($editing): ?>
		<input type="hidden" name="id" value="<?php echo $alert_rule->id; ?>">
		<?php endif; ?>

		<?php if (isset($_GET['nameErr'])): ?>
		<div class="alert alert--error"><?php echo htmlspecialchars($_GET['nameErr']); ?></div>
		<?php endif; ?>
		<input type="text" name="name" placeholder="Nom de l'alerte" value="<?php echo htmlspecialchars($editing ? $alert_rule->name : ($_GET['name'] ?? '')); ?>" required/>

		<label class="form-label" style="margin-top: 12px;">Type de déclenchement</label>
		<select name="trigger_type" id="trigger_type">
			<?php foreach ($trigger_labels as $value => $label): ?>
			<option value="<?php echo $value; ?>" <?php echo ($editing && $alert_rule->trigger_type === $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
			<?php endforeach; ?>
		</select>

		<div id="days_container" style="margin-top: 12px;">
			<label class="form-label">Nombre de jours</label>
			<input type="number" name="trigger_days" min="1" value="<?php echo $editing ? $alert_rule->trigger_days : 30; ?>" />
		</div>

		<label class="form-label" style="margin-top: 12px;">Template email</label>
		<select name="email_template">
			<?php foreach ($models as $tpl_id => $tpl_name): ?>
			<option value="<?php echo $tpl_id; ?>" <?php echo ($editing && $alert_rule->email_template == $tpl_id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tpl_name); ?></option>
			<?php endforeach; ?>
		</select>

		<div class="form-toggle" style="margin-top: 12px;">
			<span>Actif</span>
			<label class="switch"><input name="active" type="checkbox" <?php echo (!$editing || $alert_rule->active) ? 'checked' : ''; ?>><span class="slider round"></span></label>
		</div>

		<p><input type="submit" value="<?php echo $editing ? 'Modifier' : 'Créer'; ?>"/></p>
		<?php if ($editing): ?>
		<p><a href="alertRules.php" class="page-back">Annuler</a></p>
		<?php endif; ?>
	</form>
</div>

<script>
document.getElementById('trigger_type').addEventListener('change', function() {
	document.getElementById('days_container').style.display = this.value === 'on' ? 'none' : 'block';
});
if (document.getElementById('trigger_type').value === 'on') {
	document.getElementById('days_container').style.display = 'none';
}
</script>

<?php if (!empty($rules)): ?>
<div style="font-size: 0.8em; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Règles configurées</div>
<?php foreach ($rules as $rule): ?>
<div class="result-card" style="opacity: <?php echo $rule->active ? '1' : '0.5'; ?>;">
	<div class="result-card-body" onclick="location='alertRules.php?id=<?php echo $rule->id; ?>'">
		<div class="result-card-name"><?php echo htmlspecialchars($rule->name); ?></div>
		<div class="result-card-detail">
			<?php
				echo $trigger_labels[$rule->trigger_type];
				if ($rule->trigger_type !== 'on') {
					echo ' — ' . $rule->trigger_days . ' jour' . ($rule->trigger_days > 1 ? 's' : '');
				}
				echo ' — ' . htmlspecialchars($models[$rule->email_template] ?? 'Template inconnu');
			?>
		</div>
		<div class="result-card-detail"><?php echo $rule->active ? 'Actif' : 'Inactif'; ?></div>
	</div>
	<div style="display: flex; align-items: center; gap: 12px;">
		<button type="button" onclick="location='alertRules.php?toggle=<?php echo $rule->id; ?>'" style="background: none; border: none; cursor: pointer; font-size: 1.2em; color: <?php echo $rule->active ? '#2ab934' : '#999'; ?>;" title="<?php echo $rule->active ? 'Désactiver' : 'Activer'; ?>"><?php echo $rule->active ? '&#10004;' : '&#10006;'; ?></button>
		<button type="button" onclick="confirm_action('Supprimer l\'alerte ?', 'alertRules.php?delete=<?php echo $rule->id; ?>')" class="result-card-delete">&#10005;</button>
	</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<p><a href="main.php" class="page-back">Retour</a></p>
</div>
</body>
</html>
