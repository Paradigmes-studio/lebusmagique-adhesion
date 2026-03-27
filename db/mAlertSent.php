<?php
require_once("db/adhesionClient.php");

class mAlertSent {
	private $conn, $conf;

	public function __construct($conn, $conf) {
		$this->conn = $conn;
		$this->conf = $conf;
	}

	public function has_been_sent($alert_rule_id, $adhesion_client_id) {
		$query = $this->conn->prepare(
			"SELECT COUNT(1) FROM adh_alert_sent WHERE alert_rule_id = :rule_id AND adhesion_client_id = :client_id"
		);
		$query->bindValue(':rule_id', $alert_rule_id, PDO::PARAM_INT);
		$query->bindValue(':client_id', $adhesion_client_id, PDO::PARAM_INT);
		$query->execute();
		return $query->fetch()[0] > 0;
	}

	public function mark_sent($alert_rule_id, $adhesion_client_id) {
		$query = $this->conn->prepare(
			"INSERT IGNORE INTO adh_alert_sent(alert_rule_id, adhesion_client_id, sent_at) VALUES (:rule_id, :client_id, NOW())"
		);
		$query->bindValue(':rule_id', $alert_rule_id, PDO::PARAM_INT);
		$query->bindValue(':client_id', $adhesion_client_id, PDO::PARAM_INT);
		$query->execute();
	}

	public function get_eligible_clients($alert_rule) {
		switch ($alert_rule->trigger_type) {
			case 'before':
				$condition = "DATE(ac.date_fin) <= DATE_ADD(CURDATE(), INTERVAL :days DAY) AND ac.date_fin > NOW()";
				break;
			case 'on':
				$condition = "DATE(ac.date_fin) = CURDATE()";
				break;
			case 'after':
				$condition = "DATE_ADD(DATE(ac.date_fin), INTERVAL :days DAY) <= CURDATE()";
				break;
			default:
				return [];
		}

		$sql = "SELECT ac.* FROM adh_adhesion_client ac
			LEFT JOIN adh_alert_sent als ON als.adhesion_client_id = ac.id AND als.alert_rule_id = :rule_id
			WHERE als.id IS NULL
			AND ac.email IS NOT NULL AND ac.email != ''
			AND " . $condition;

		$query = $this->conn->prepare($sql);
		$query->bindValue(':rule_id', $alert_rule->id, PDO::PARAM_INT);
		if ($alert_rule->trigger_type !== 'on') {
			$query->bindValue(':days', $alert_rule->trigger_days, PDO::PARAM_INT);
		}
		$query->execute();

		$clients = [];
		while ($res = $query->fetch()) {
			$client = new AdhesionClient();
			$client->id = (int)$res['id'];
			$client->last_name = $res['last_name'];
			$client->first_name = $res['first_name'];
			$client->email = $res['email'];
			$client->adhesion_type = $res['adhesion_type'];
			$client->date_debut = $res['date_debut'];
			$client->date_fin = $res['date_fin'];
			$client->newsletter = $res['newsletter'];
			$client->new = false;
			$clients[] = $client;
		}
		return $clients;
	}
}
