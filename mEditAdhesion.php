<?php
require_once("db/mAdhesionClient.php");
require_once("db/mAdhesionType.php");
require_once("lib/AdhesionEdit.php");
require_once("lib/BrevoHandler.php");
require_once("init.php");

$domain_key = $domain . 'edit_adhesion_id';
$session_id = (int)($_SESSION[$domain_key] ?? 0);

if ($session_id <= 0) {
	http_response_code(403);
	die('Session invalide. Ouvre à nouveau ton lien d\'édition.');
}

$clientManager = new mAdhesionClient($conn, $conf);
$typeManager = new mAdhesionType($conn, $conf);

$result = process_edit_post($_POST, $session_id, $clientManager, $typeManager);

if (!$result['ok']) {
	$params = http_build_query([
		'error' => $result['error'],
		'email' => $_POST['email'] ?? '',
		'renewal_date' => $_POST['renewal_date'] ?? '',
	]);
	header('Location: editAdhesion.php?' . $params);
	exit;
}

$adhesion = $result['adhesion'];

if (!empty($result['renewed'])) {
	$mailRes = regenerate_card_and_send_welcome($adhesion, $typeManager, $conn, $conf);
	if (!$mailRes['ok']) {
		error_log(sprintf("Self-edit renewal email failed for adhesion %d: %s", $adhesion->id, $mailRes['error'] ?? '?'));
	}
}

$brevo = new BrevoHandler($conn, $conf);
if ($brevo->isConfigured()) {
	$listIds = [];
	$unlinkListIds = [];
	if (!empty($conf['brevoListIdAdhesion'])) {
		$listIds[] = (int)$conf['brevoListIdAdhesion'];
	}
	$subscribed = (($_POST['subscribe'] ?? '') === 'on');
	if (!empty($conf['brevoListIdNewsletter'])) {
		if ($subscribed) {
			$listIds[] = (int)$conf['brevoListIdNewsletter'];
		} else {
			$unlinkListIds[] = (int)$conf['brevoListIdNewsletter'];
		}
	}
	$res = $brevo->upsertContact($adhesion, $listIds, $unlinkListIds);
	if (!$res['ok']) {
		error_log(sprintf("Brevo sync (self-edit) failed: HTTP %s - %s", $res['http'] ?? '?', $res['body'] ?? $res['msg'] ?? ''));
	}
}

unset($_SESSION[$domain_key]);

$title = "Merci !";
$text = "Tes informations ont bien été mises à jour.";
header(sprintf('Location: message.php?title=%s&text=%s&nextPage=%s&buttonName=%s', rawurlencode($title), rawurlencode($text), 'main.php', rawurlencode('OK')));
exit;
