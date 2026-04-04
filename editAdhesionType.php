<?php
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
</head>
<body class="defaultback">
<div class="page">
<div class="page-title"><?php echo $new ? 'Nouveau type d\'adhésion' : 'Modifier le type'; ?></div>
<form action="mEditAdhesionType.php" method="POST">
<?php
	if (!$new)
		printf('<input type="hidden" name="id" value="%d">', $adhesion_type->id);
	printf('<input type="hidden" name="new" value="%d">', $new);

	// Name
	if (isset($_GET['nameErr']))
		printf('<div class="alert alert--error">%s</div>', htmlspecialchars($_GET['nameErr']));
	$name_val = htmlspecialchars(isset($_GET['name']) ? $_GET['name'] : $adhesion_type->name);
	printf('<input type="text" maxlength="200" name="name" value="%s" placeholder="Nom de l\'adhesion" %s/>', $name_val, isset($_GET['nameErr']) ? 'class="FieldError"' : '');

	// Price
	$price_val = isset($_GET['price']) ? $_GET['price'] : $adhesion_type->price;
	print('<label class="form-label">Prix (0 = prix libre)</label>');
	printf('<input type="number" name="price" min="0" step="0.01" value="%.2f" placeholder="Prix"/>', $price_val);

	// Email model
	print('<label class="form-label">Email de bienvenue</label>');
	require_once("db/mEmailTemplate.php");
	$tplManager = new mEmailTemplate($conn, $conf);
	$models = $tplManager->list_names();
	print('<select name="email_welcome">');
	foreach($models as $tpl_id => $tpl_name) {
		$s = (!$adhesion_type->new && $adhesion_type->email_welcome == $tpl_id) ? 'selected' : '';
		printf('<option value="%d" %s>%s</option>', $tpl_id, $s, htmlspecialchars($tpl_name));
	}
	print('</select>');

	// Duration
	if (isset($_GET['durationErr']))
		printf('<div class="alert alert--error">%s</div>', htmlspecialchars($_GET['durationErr']));
	$dur_val = htmlspecialchars(isset($_GET['duration']) ? $_GET['duration'] : $adhesion_type->duration);
	print('<label class="form-label">Durée de validité (jours)</label>');
	printf('<input type="number" name="duration" min="0" max="720" step="1" value="%s" placeholder="Duree en jours" %s/>', $dur_val, isset($_GET['durationErr']) ? 'class="FieldError"' : '');
?>
<p><input type="submit" value="Enregistrer"/></p>
<p><a href="listAdhesionType.php" class="page-back">Annuler</a></p>
</form>
</div>
</body>
</html>
