<?php
require_once("db/alertRule.php");

class mAlertRule {
	private $conn, $conf;

	public function __construct($conn, $conf) {
		$this->conn = $conn;
		$this->conf = $conf;
	}

	public function read($id, $alert_rule) {
		$query = $this->conn->prepare("SELECT * FROM adh_alert_rule WHERE id = :id");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();

		if ($res = $query->fetch()) {
			$alert_rule->id = (int)$res['id'];
			$alert_rule->name = $res['name'];
			$alert_rule->trigger_type = $res['trigger_type'];
			$alert_rule->trigger_days = (int)$res['trigger_days'];
			$alert_rule->email_template = $res['email_template'];
			$alert_rule->active = (int)$res['active'];
			return true;
		}
		return false;
	}

	public function list_all() {
		$query = $this->conn->query("SELECT * FROM adh_alert_rule ORDER BY trigger_type, trigger_days");
		$rules = [];
		while ($res = $query->fetch()) {
			$rule = new AlertRule();
			$rule->id = (int)$res['id'];
			$rule->name = $res['name'];
			$rule->trigger_type = $res['trigger_type'];
			$rule->trigger_days = (int)$res['trigger_days'];
			$rule->email_template = $res['email_template'];
			$rule->active = (int)$res['active'];
			$rules[] = $rule;
		}
		return $rules;
	}

	public function list_active() {
		$query = $this->conn->query("SELECT * FROM adh_alert_rule WHERE active = 1");
		$rules = [];
		while ($res = $query->fetch()) {
			$rule = new AlertRule();
			$rule->id = (int)$res['id'];
			$rule->name = $res['name'];
			$rule->trigger_type = $res['trigger_type'];
			$rule->trigger_days = (int)$res['trigger_days'];
			$rule->email_template = $res['email_template'];
			$rule->active = (int)$res['active'];
			$rules[] = $rule;
		}
		return $rules;
	}

	public function write($alert_rule) {
		if (isset($alert_rule->id) && $alert_rule->id > 0) {
			$query = $this->conn->prepare("UPDATE adh_alert_rule SET name = :name, trigger_type = :trigger_type, trigger_days = :trigger_days, email_template = :email_template, active = :active WHERE id = :id");
			$query->bindValue(':id', $alert_rule->id, PDO::PARAM_INT);
		} else {
			$query = $this->conn->prepare("INSERT INTO adh_alert_rule(name, trigger_type, trigger_days, email_template, active) VALUES (:name, :trigger_type, :trigger_days, :email_template, :active)");
		}
		$query->bindValue(':name', $alert_rule->name, PDO::PARAM_STR);
		$query->bindValue(':trigger_type', $alert_rule->trigger_type, PDO::PARAM_STR);
		$query->bindValue(':trigger_days', $alert_rule->trigger_days, PDO::PARAM_INT);
		$query->bindValue(':email_template', $alert_rule->email_template, PDO::PARAM_STR);
		$query->bindValue(':active', $alert_rule->active, PDO::PARAM_INT);
		$query->execute();

		if (!isset($alert_rule->id) || $alert_rule->id == 0) {
			$alert_rule->id = (int)$this->conn->lastInsertId();
		}
	}

	public function delete($id) {
		$query = $this->conn->prepare("DELETE FROM adh_alert_rule WHERE id = :id");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
	}

	public function toggle_active($id) {
		$query = $this->conn->prepare("UPDATE adh_alert_rule SET active = NOT active WHERE id = :id");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
	}
}
