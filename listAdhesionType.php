<?php
require_once("db/mAdhesionType.php");
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
<div class="page">
<div class="page-title">Types d'adhesion</div>
<?php
	$t = new mAdhesionType($conn, $conf);
	$adhesion_types = $t->list_adhesion_type();
	foreach ($adhesion_types as $adhesion_type) {
?>
	<div class="result-card">
		<div class="result-card-body" onclick="location='editAdhesionType.php?id=<?php echo $adhesion_type->id; ?>'">
			<div class="result-card-name"><?php echo htmlspecialchars($adhesion_type->name); ?></div>
			<div class="result-card-detail"><?php echo $adhesion_type->price > 0 ? sprintf('%.2f&euro;', $adhesion_type->price) : 'Prix libre'; ?> &mdash; <?php echo $adhesion_type->duration; ?> jours</div>
		</div>
		<button type="button" onclick="confirm_action('Supprimer le type <?php echo htmlspecialchars($adhesion_type->name); ?> ?', 'deleteAdhesionType.php?id=<?php echo $adhesion_type->id; ?>')" class="result-card-delete">&#10005;</button>
	</div>
<?php } ?>
<p><a href="editAdhesionType.php" class="btn btn--add">+ Nouveau type</a></p>
<p><a href="main.php" class="page-back">Retour</a></p>
</div>
</body>
</html>

