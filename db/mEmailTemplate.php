<?php
require_once("db/emailTemplate.php");

class mEmailTemplate {
	private $conn, $conf;

	public function __construct($conn, $conf) {
		$this->conn = $conn;
		$this->conf = $conf;
	}

	public function read($id, $template) {
		$query = $this->conn->prepare("SELECT * FROM adh_email_template WHERE id = :id");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();

		if ($res = $query->fetch()) {
			$template->id = (int)$res['id'];
			$template->name = $res['name'];
			$template->subject = $res['subject'];
			$template->body = $res['body'];
			return true;
		}
		return false;
	}

	public function list_all() {
		$query = $this->conn->query("SELECT * FROM adh_email_template ORDER BY name");
		$templates = [];
		while ($res = $query->fetch()) {
			$t = new EmailTemplate();
			$t->id = (int)$res['id'];
			$t->name = $res['name'];
			$t->subject = $res['subject'];
			$t->body = $res['body'];
			$templates[] = $t;
		}
		return $templates;
	}

	public function list_names() {
		$query = $this->conn->query("SELECT id, name FROM adh_email_template ORDER BY name");
		$templates = [];
		while ($res = $query->fetch()) {
			$templates[(int)$res['id']] = $res['name'];
		}
		return $templates;
	}

	public function write($template) {
		if (isset($template->id) && $template->id > 0) {
			$query = $this->conn->prepare("UPDATE adh_email_template SET name = :name, subject = :subject, body = :body WHERE id = :id");
			$query->bindValue(':id', $template->id, PDO::PARAM_INT);
		} else {
			$query = $this->conn->prepare("INSERT INTO adh_email_template(name, subject, body) VALUES (:name, :subject, :body)");
		}
		$query->bindValue(':name', $template->name, PDO::PARAM_STR);
		$query->bindValue(':subject', $template->subject, PDO::PARAM_STR);
		$query->bindValue(':body', $template->body, PDO::PARAM_STR);
		$query->execute();

		if (!isset($template->id) || $template->id == 0) {
			$template->id = (int)$this->conn->lastInsertId();
		}
	}

	public function delete($id) {
		$query = $this->conn->prepare("DELETE FROM adh_email_template WHERE id = :id");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
	}
}
