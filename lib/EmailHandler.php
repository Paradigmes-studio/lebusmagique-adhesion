<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once('ext_lib/PHPMailer/src/Exception.php');
require_once('ext_lib/PHPMailer/src/PHPMailer.php');
require_once('ext_lib/PHPMailer/src/SMTP.php');

class EmailHandler {
	private $conf, $conn;

	public function __construct($conn, $conf) {
		$this->conn=$conn;
		$this->conf=$conf;
	}

	private function init_email_smtp($email) {
		$email->IsSMTP();
		$email->Host = $this->conf['smtp_server'];
		$email->Port = $this->conf['smtp_port'];
		if (!empty($this->conf['smtp_username'])) {
			$email->SMTPAuth = true;
			$email->SMTPSecure = 'tls';
			$email->Username = $this->conf['smtp_username'];
			$email->Password = $this->conf['smtp_password'];
		}
		$email->SetFrom($this->conf['email_from'], $this->conf['name_company']);
		#$email->SMTPDebug = true;
	}

	private function replace_variables($text, $adhesionClient) {
		$replacements = [
			'{prenom}' => $adhesionClient->first_name ?? '',
			'{nom}' => $adhesionClient->last_name ?? '',
			'{email}' => $adhesionClient->email ?? '',
			'{type_adhesion}' => $adhesionClient->adhesion_type ?? '',
			'{date_debut}' => !empty($adhesionClient->date_debut) ? date('d/m/Y', strtotime($adhesionClient->date_debut)) : '',
			'{date_fin}' => !empty($adhesionClient->date_fin) ? date('d/m/Y', strtotime($adhesionClient->date_fin)) : '',
			'{id}' => $adhesionClient->id ?? '',
		];
		return str_replace(array_keys($replacements), array_values($replacements), $text);
	}

	private function load_template($template_id) {
		if (is_numeric($template_id)) {
			$query = $this->conn->prepare("SELECT subject, body FROM adh_email_template WHERE id = :id");
			$query->bindValue(':id', $template_id, PDO::PARAM_INT);
		} else {
			$query = $this->conn->prepare("SELECT subject, body FROM adh_email_template WHERE name = :name");
			$query->bindValue(':name', $template_id, PDO::PARAM_STR);
		}
		$query->execute();
		return $query->fetch();
	}

	public function send_adhesion($adhesionClient, $template_id, &$error) {
		$error = "";

		$tpl = $this->load_template($template_id);
		if (!$tpl) {
			$error = "Template not found";
			return;
		}

		$subject = $this->replace_variables($tpl['subject'], $adhesionClient);
		$body = $this->replace_variables($tpl['body'], $adhesionClient);

		$email = new PHPMailer(true);
		try {
			$this->init_email_smtp($email);
			$email->AddReplyTo($this->conf["adhesion_reply"], $this->conf["name_company"]);
			$email->AddAddress($adhesionClient->email);
			$email->Subject = $subject;
			$email->isHTML(true);
			$email->CharSet = "utf-8";
			$status = false;
			while ($status != true) {
				$status = file_exists('res/Carte' . $adhesionClient->id . '.jpg');
				if ($status == true)
					break;
			}
			$email->AddAttachment('res/Carte' . $adhesionClient->id . '.jpg');
			$email->Body = $body;
			$email->Send();
		} catch (phpmailerException $e) {
			$error = $e->getMessage();
		} catch (Exception $e) {
			$error = $e->getMessage();
		}
	}

	public function send_alert($adhesionClient, $template_id, &$error) {
		$error = "";

		$tpl = $this->load_template($template_id);
		if (!$tpl) {
			$error = "Template not found";
			return;
		}

		$subject = $this->replace_variables($tpl['subject'], $adhesionClient);
		$body = $this->replace_variables($tpl['body'], $adhesionClient);

		$email = new PHPMailer(true);
		try {
			$this->init_email_smtp($email);
			$email->AddReplyTo($this->conf["adhesion_reply"], $this->conf["name_company"]);
			$email->AddAddress($adhesionClient->email);
			$email->Subject = $subject;
			$email->isHTML(true);
			$email->CharSet = "utf-8";
			$email->Body = $body;
			$email->Send();
		} catch (\PHPMailer\PHPMailer\Exception $e) {
			$error = $e->getMessage();
		} catch (Exception $e) {
			$error = $e->getMessage();
		}
	}

	public function get_models() {
		$query = $this->conn->query("SELECT id, name FROM adh_email_template ORDER BY name");
		$models = [];
		while ($res = $query->fetch()) {
			$models[(int)$res['id']] = $res['name'];
		}
		return $models;
	}
}

?>
