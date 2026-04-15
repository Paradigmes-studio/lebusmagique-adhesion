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

		return $this->request('POST', 'https://api.brevo.com/v3/contacts', $payload);
	}

	public function removeFromList($email, $listId) {
		if (!$this->isConfigured() || empty($listId)) {
			return ['ok' => false, 'msg' => 'Brevo not configured or empty listId'];
		}
		$url = 'https://api.brevo.com/v3/contacts/lists/' . (int)$listId . '/contacts/remove';
		return $this->request('POST', $url, ['emails' => [$email]]);
	}

	private function request($method, $url, array $payload) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'accept: application/json',
			'content-type: application/json',
			'api-key: ' . $this->conf['brevoApiKey'],
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
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
