<?php
require_once("db/mAdhesionClient.php");
require_once("init.php");
require_once("get_login_info.php");
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
<div class="page-title">Résultats</div>
<?php
	$filters = [];
	if (!empty($_POST['last_name']))
		$filters['last_name'] = $_POST['last_name'];
	if (!empty($_POST['first_name']))
		$filters['first_name'] = $_POST['first_name'];
	if (!empty($_POST['email']))
		$filters['email'] = $_POST['email'];
	if (!empty($_POST['adherent_id']))
		$filters['adherent_id'] = $_POST['adherent_id'];

	$ac = new mAdhesionClient($conn, $conf);

	try {
		$adhesions = $ac->search($filters);
	} catch (\Exception $e) {
		print(htmlspecialchars($e->getMessage()));
	}

	if (empty($adhesions)) {
		print('<div class="alert">Aucun résultat</div>');
	}

	foreach($adhesions as $adhesion) {
		$expired = date_format(new DateTime($adhesion->date_fin), 'Y-m-d') < date('Y-m-d');
		$card_class = $expired ? 'result-card result-card--expired' : 'result-card';
?>
	<div class="<?php echo $card_class; ?>">
		<div class="result-card-body" onclick="location='createAdhesionClient.php?id=<?php echo $adhesion->id; ?>'">
			<div class="result-card-id">#<?php echo $adhesion->id; ?></div>
			<div class="result-card-name"><?php echo htmlspecialchars($adhesion->first_name) . ' ' . htmlspecialchars($adhesion->last_name); ?></div>
			<div class="result-card-detail"><?php echo htmlspecialchars($adhesion->adhesion_type); ?> &mdash; <?php echo date_format(new DateTime($adhesion->date_fin), 'd/m/Y'); ?></div>
			<div class="result-card-detail"><?php echo htmlspecialchars($adhesion->email); ?></div>
		</div>
		<button type="button" onclick="confirm_action('Supprimer l\'adhérent #<?php echo $adhesion->id; ?> ?', 'mDeleteAdhesionClient.php?id=<?php echo $adhesion->id; ?>')" class="result-card-delete">&#10005;</button>
	</div>
<?php
	}
?>
<p><a href="searchAdhesionClient.php" class="page-back">Retour</a></p>
</div>
</body>
</html>
