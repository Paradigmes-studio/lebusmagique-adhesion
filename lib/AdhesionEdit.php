<?php

function regenerate_adhesion_card($adhesion): bool {
	$carteFile = 'res/Carte' . (int)$adhesion->id . '.jpg';
	if (file_exists($carteFile)) {
		@unlink($carteFile);
	}
	if (!class_exists('ImageCarteAdhesion')) {
		require_once 'lib/CreateImageText.php';
	}
	$img = new ImageCarteAdhesion();
	return (bool)$img->generate($adhesion);
}

function send_adhesion_welcome_email($adhesion, string $model, $conn, array $conf): array {
	if (empty($conf['send_email'])) {
		return ['ok' => true, 'skipped' => true];
	}
	if (!class_exists('EmailHandler')) {
		require_once 'lib/EmailHandler.php';
	}
	$handler = new EmailHandler($conn, $conf);
	$err = '';
	$handler->send_adhesion($adhesion, $model, $err);
	if ($err !== '') {
		return ['ok' => false, 'error' => $err];
	}
	return ['ok' => true];
}

function regenerate_card_and_send_welcome($adhesion, mAdhesionType $typeManager, $conn, array $conf): array {
	if (!regenerate_adhesion_card($adhesion)) {
		return ['ok' => false, 'error' => 'Erreur à la génération de la carte adhérent'];
	}
	$type = new AdhesionType();
	try {
		$typeManager->read_by_name($adhesion->adhesion_type, $type);
	} catch (Exception $e) {
		return ['ok' => false, 'error' => 'Type d\'adhésion introuvable'];
	}
	$model = $type->email_welcome;
	if ($model === null || $model === '') {
		return ['ok' => true, 'skipped' => true];
	}
	return send_adhesion_welcome_email($adhesion, $model, $conn, $conf);
}

function is_valid_daily_code(string $code, ?int $timestamp = null): bool {
	if ($code === '' || strlen($code) !== 4) {
		return false;
	}
	$timestamp = $timestamp ?? time();
	$day = date('d', $timestamp);
	$month = date('m', $timestamp);
	return strrev(substr($code, 0, 2)) === $day
		&& strrev(substr($code, 2, 2)) === $month;
}

function compute_renewal_dates(string $renewal_date, int $duration_days): ?array {
	if ($renewal_date === '') {
		return null;
	}
	$start_ts = strtotime($renewal_date);
	if ($start_ts === false) {
		return null;
	}
	$start = date('Y-m-d H:i:s', $start_ts);
	$end = date('Y-m-d H:i:s', strtotime('+' . $duration_days . ' days', $start_ts));
	return [$start, $end];
}

function process_edit_post(array $input, int $session_id, mAdhesionClient $clientManager, mAdhesionType $typeManager): array {
	$code = $input['code_valid'] ?? '';
	if (!is_valid_daily_code($code)) {
		return ['ok' => false, 'error' => 'Code du jour invalide'];
	}

	$email = preg_replace('/\s+/', '', (string)($input['email'] ?? ''));
	if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
		return ['ok' => false, 'error' => 'Email invalide'];
	}

	$adhesion = new AdhesionClient();
	if (!$clientManager->read($session_id, $adhesion)) {
		return ['ok' => false, 'error' => 'Adhésion introuvable'];
	}

	$adhesion->email = $email;
	$adhesion->newsletter = (($input['subscribe'] ?? '') === 'on') ? 1 : 0;

	$renewed = false;
	$renew = (($input['renew'] ?? '') === 'on');
	if ($renew) {
		$today = date('Y-m-d');
		$endDate = date('Y-m-d', strtotime($adhesion->date_fin));
		if ($endDate > $today) {
			return ['ok' => false, 'error' => 'Ton adhésion est encore valide jusqu\'au ' . date('d/m/Y', strtotime($adhesion->date_fin)) . ', tu ne peux pas la renouveler pour le moment.'];
		}
		$type = new AdhesionType();
		try {
			$typeManager->read_by_name($adhesion->adhesion_type, $type);
		} catch (Exception $e) {
			return ['ok' => false, 'error' => 'Type d\'adhésion introuvable'];
		}
		$dates = compute_renewal_dates($today, (int)$type->duration);
		[$adhesion->date_debut, $adhesion->date_fin] = $dates;
		$renewed = true;
	}

	$clientManager->write($adhesion);

	return ['ok' => true, 'adhesion' => $adhesion, 'renewed' => $renewed];
}
