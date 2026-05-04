<?php

class BrevoHandler {
	private $conf, $conn;

	public function __construct($conn, $conf) {
		$this->conn = $conn;
		$this->conf = $conf;
	}

	public function isConfigured() {
		return !empty($this->conf['brevoApiKey']) && $this->conf['brevoApiKey'] !== 'BREVO_API_KEY';
	}

	public function upsertContact($adhesion_client, array $listIds, array $unlinkListIds = []) {
		if (!$this->isConfigured()) {
			return ['ok' => false, 'msg' => 'Brevo API key not configured'];
		}
		$payload = $this->buildPayload($adhesion_client, $listIds, $unlinkListIds);
		return $this->request('POST', 'https://api.brevo.com/v3/contacts', $payload);
	}

	public function buildPayload($adhesion_client, array $listIds, array $unlinkListIds = []): array {
		$attributes = [
			'PRENOM' => $adhesion_client->first_name ?? '',
			'NOM' => $adhesion_client->last_name ?? '',
		];
		if (!empty($adhesion_client->id)) {
			$attributes['ID_ADHERENT'] = (int)$adhesion_client->id;
		}
		if (!empty($adhesion_client->referral_source)) {
			$attributes['SOURCE'] = $adhesion_client->referral_source;
		}
		if (!empty($adhesion_client->date_debut)) {
			$attributes['DATE_ADHESION'] = date('Y-m-d', strtotime($adhesion_client->date_debut));
		}
		if (!empty($adhesion_client->date_fin)) {
			$attributes['DATE_FIN'] = date('Y-m-d', strtotime($adhesion_client->date_fin));
		}
		if (!empty($adhesion_client->edit_token)) {
			$attributes['EDIT_TOKEN'] = $adhesion_client->edit_token;
		}

		$payload = [
			'email' => $adhesion_client->email,
			'attributes' => $attributes,
			'updateEnabled' => true,
		];
		if (!empty($listIds)) {
			$payload['listIds'] = array_values(array_map('intval', $listIds));
		}
		if (!empty($unlinkListIds)) {
			$payload['unlinkListIds'] = array_values(array_map('intval', $unlinkListIds));
		}
		return $payload;
	}

	public function removeFromList($email, $listId) {
		if (!$this->isConfigured() || empty($listId)) {
			return ['ok' => false, 'msg' => 'Brevo not configured or empty listId'];
		}
		$url = 'https://api.brevo.com/v3/contacts/lists/' . (int)$listId . '/contacts/remove';
		return $this->request('POST', $url, ['emails' => [$email]]);
	}

	public function getContact($email) {
		if (!$this->isConfigured()) {
			return ['ok' => false, 'msg' => 'Brevo API key not configured'];
		}
		if (empty($email)) {
			return ['ok' => false, 'msg' => 'Empty email'];
		}
		$url = 'https://api.brevo.com/v3/contacts/' . rawurlencode($email);
		$res = $this->request('GET', $url);
		$res['listIds'] = $this->parseListIdsFromBody($res['body'] ?? '');
		return $res;
	}

	public function isInList($email, $listId) {
		if (empty($email) || empty($listId) || !$this->isConfigured()) {
			return null;
		}
		$res = $this->getContact($email);
		if ($res['ok']) {
			return in_array((int)$listId, $res['listIds'] ?? [], true);
		}
		if ((int)($res['http'] ?? 0) === 404) {
			return false;
		}
		return null;
	}

	public function parseListIdsFromBody($body): array {
		$data = json_decode((string)$body, true);
		if (!is_array($data) || !isset($data['listIds']) || !is_array($data['listIds'])) {
			return [];
		}
		return array_values(array_map('intval', $data['listIds']));
	}

	private function request($method, $url, ?array $payload = null) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'accept: application/json',
			'content-type: application/json',
			'api-key: ' . $this->conf['brevoApiKey'],
		]);
		if ($payload !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$ok = $httpCode >= 200 && $httpCode < 300;
		return ['ok' => $ok, 'http' => $httpCode, 'body' => $result];
	}
}
?>
