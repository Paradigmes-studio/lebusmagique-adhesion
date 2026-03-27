<?php
require_once("db/mEmailTemplate.php");
require_once("init.php");
require_once("get_login_info.php");

$manager = new mEmailTemplate($conn, $conf);

$editing = isset($_GET['id']);
$template = new EmailTemplate();
if ($editing) {
	$manager->read($_GET['id'], $template);
}

if (isset($_GET['delete'])) {
	$manager->delete($_GET['delete']);
	header('Location: emailTemplates.php');
	exit;
}

if (isset($_GET['clone'])) {
	$source = new EmailTemplate();
	if ($manager->read($_GET['clone'], $source)) {
		$clone = new EmailTemplate();
		$clone->name = $source->name . ' (copie)';
		$clone->subject = $source->subject;
		$clone->body = $source->body;
		$manager->write($clone);
		header('Location: emailTemplates.php?id=' . $clone->id . '&success=Template cloné');
	} else {
		header('Location: emailTemplates.php');
	}
	exit;
}

$templates = $manager->list_all();

$variables = [
	'{prenom}' => 'Prénom',
	'{nom}' => 'Nom',
	'{email}' => 'Email',
	'{type_adhesion}' => "Type d'adhésion",
	'{date_debut}' => 'Date de début',
	'{date_fin}' => "Date de fin",
	'{id}' => "N° d'adhérent",
];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="mobile.css">
<script src='lib/utils.js'></script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
</head>
<body class="defaultback">
<div class="page page--wide">
<div class="page-title">Templates email</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success"><?php echo htmlspecialchars($_GET['success']); ?></div>
<?php endif; ?>

<div style="background: white; border-radius: 16px; padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
	<div style="font-size: 0.8em; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
		<?php echo $editing ? 'Modifier le template' : 'Nouveau template'; ?>
	</div>
	<form action="mEmailTemplates.php" method="POST">
		<?php if ($editing): ?>
		<input type="hidden" name="id" value="<?php echo $template->id; ?>">
		<?php endif; ?>

		<?php if (isset($_GET['nameErr'])): ?>
		<div class="alert alert--error"><?php echo htmlspecialchars($_GET['nameErr']); ?></div>
		<?php endif; ?>
		<input type="text" name="name" placeholder="Nom du template" value="<?php echo htmlspecialchars($editing ? $template->name : ($_GET['name'] ?? '')); ?>" required/>

		<?php if (isset($_GET['subjectErr'])): ?>
		<div class="alert alert--error"><?php echo htmlspecialchars($_GET['subjectErr']); ?></div>
		<?php endif; ?>
		<input type="text" name="subject" placeholder="Sujet de l'email" value="<?php echo htmlspecialchars($editing ? $template->subject : ($_GET['subject'] ?? '')); ?>" required style="margin-top: 12px;"/>

		<label class="form-label" style="margin-top: 12px;">Variables disponibles</label>
		<div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
			<?php foreach ($variables as $var => $label): ?>
			<button type="button" class="var-btn" onclick="insertVariable('<?php echo $var; ?>')" style="background: #f0ece0; border: none; border-radius: 6px; padding: 4px 10px; font-size: 0.85em; cursor: pointer; font-family: 'Barlow Condensed', sans-serif;"><?php echo htmlspecialchars($var); ?> <span style="color: #999;"><?php echo htmlspecialchars($label); ?></span></button>
			<?php endforeach; ?>
		</div>

		<label class="form-label">Contenu</label>
		<textarea name="body" id="template_body"><?php echo htmlspecialchars($editing ? $template->body : ''); ?></textarea>

		<p><input type="submit" value="<?php echo $editing ? 'Modifier' : 'Créer'; ?>"/></p>
		<?php if ($editing): ?>
		<p><a href="emailTemplates.php" class="page-back">Annuler</a></p>
		<?php endif; ?>
	</form>
</div>

<?php if (!empty($templates)): ?>
<div style="font-size: 0.8em; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Templates existants</div>
<?php foreach ($templates as $tpl): ?>
<div class="result-card">
	<div class="result-card-body" onclick="location='emailTemplates.php?id=<?php echo $tpl->id; ?>'">
		<div class="result-card-name"><?php echo htmlspecialchars($tpl->name); ?></div>
		<div class="result-card-detail"><?php echo htmlspecialchars($tpl->subject); ?></div>
	</div>
	<div style="display: flex; align-items: center; gap: 12px;">
		<button type="button" onclick="location='emailTemplates.php?clone=<?php echo $tpl->id; ?>'" style="background: none; border: none; cursor: pointer; font-size: 1.2em;" title="Cloner">&#128203;</button>
		<button type="button" onclick="confirm_action('Supprimer le template ?', 'emailTemplates.php?delete=<?php echo $tpl->id; ?>')" class="result-card-delete">&#10005;</button>
	</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<p><a href="main.php" class="page-back">Retour</a></p>
</div>

<script>
tinymce.init({
	selector: '#template_body',
	height: 400,
	menubar: false,
	plugins: 'lists link code fullscreen',
	toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link | code fullscreen',
	content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }'
});

function insertVariable(varName) {
	tinymce.activeEditor.execCommand('mceInsertContent', false, varName);
}
</script>
</body>
</html>
