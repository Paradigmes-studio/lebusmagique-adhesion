<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once('ext_lib/PHPMailer/src/Exception.php');
require_once('ext_lib/PHPMailer/src/PHPMailer.php');
require_once('ext_lib/PHPMailer/src/SMTP.php'); 

class EmailHandler {
	private $conf, $conn, $models_dir;

	public function __construct($conn, $conf) {
		$this->conn=$conn;
		$this->conf=$conf;
		$this->models_dir="res"; 
	}
	
	private function init_email_smtp($email) {
		$email->IsSMTP(); // telling the class to use SMTP
		$email->SMTPAuth = true;
		$email->Host = $this->conf['smtp_server'];
		$email->Port = $this->conf['smtp_port'];
		$email->Username = $this->conf['smtp_username'];
		$email->Password = $this->conf['smtp_password'];
		$email->SetFrom($this->conf['email_from'], $this->conf['name_company']);
		#$email->SMTPDebug = true;
	} 

/*	private function save_email($recipients, $subject, $body) {
		// save email to the table instead.
		$query=$this->conn->prepare("INSERT INTO email_sent(date_, recipients, subject, body) VALUES (NOW(), :recipients, :subject, :body)");
		$query->bindValue(":recipients", $recipients, PDO::PARAM_STR);
		$query->bindValue(":subject", $subject, PDO::PARAM_STR);
		$query->bindValue(":body", $body, PDO::PARAM_STR);
		$query->execute(); 
	}*/

/*	public function send_email_summary($tour, $tips, $count, $tour_type, $error_sending_email, &$error) {
		$email_model=$tour_type->email_end_of_tour;
		$error="";
		$subject=sprintf('A tour was done by %s', $tour->user);
		$recipient=$this->conf['dest_email_summary_end_of_tour'];
		$body=sprintf("Date: %s\nGuide: %s\nTour: %s\nVisitors: %d\nTips: %0.2f\n", $tour->date->format("Y-m-d"), $tour->user, $tour_type->name, $count, $tips);
		if ($email_model != "") {
			$recipients = $this->get_recipients($tour->id);
			$body.=sprintf("Emails sent: %d",  sizeof($recipients));
			if ($error_sending_email!='') {
				$body.=sprintf("\nErrors while sending emails: %s", $error_sending_email);
			}
		}
		if (!($this->actually_send_mail())) {
			$this->save_email($recipient, $subject, $body);
		} else { 
			$email=new PHPMailer(true);
			$error="";
			try {
				$this->init_email_smtp($email); 
				$addresses = explode(',', $recipient);
				foreach ($addresses as $address) {
					$email->AddAddress($address);
				}
				$email->Subject=$subject;
				$email->isHTML(false); 
				$email->CharSet="utf-8";
				$email->Body=$body;
				$email->Send();
			} catch (phpmailerException $e) { 
				$error=$e->getMessage();
			} catch (Exception $e) {
				$error=$e->getMessage();
			} 
		}
	}*/
/*
	public function send_simple_email($recipient, $subject, $body, &$error) {
		$error = "";
		if (!($this->actually_send_mail())) {
			$this->save_email($recipient, $subject, $body);
			return "";
		} else { 
			$email=new PHPMailer(true);
			$error="";
			try {
				$this->init_email_smtp($email); 
				$addresses = explode(',', $recipient);
				foreach ($addresses as $address) {
					$email->AddAddress($address);
				}
				$email->Subject=$subject;
				$email->isHTML(false); 
				$email->CharSet="utf-8";
				$email->Body=$body;
				$email->Send();
			} catch (phpmailerException $e) { 
				$error=$e->getMessage();
			} catch (Exception $e) {
				$error=$e->getMessage();
			} 
		}
	}
*/
	public function send_adhesion($adhesionClient, $model, &$error) { 
		
		// email for the client
		$error = "";
		$recipient = $adhesionClient->email; 
		$subject = "Bienvenue à bord Moussaillon!";
		$body = "";
		$conf = $this->conf;

		$file = $this->models_dir.'/'.$model;
		if (substr($file, -5) == '.html') {
			$body = file_get_contents($file);
		} 
		if (substr($file, -4) == '.php') {
			include($file); // TODO security leak ?
		} 

		$email=new PHPMailer(true);
		$error="";
		try {
			//TOlaterDO later: handle attachments in case the user select an html file
			$this->init_email_smtp($email); 
			$email->AddReplyTo($this->conf["adhesion_reply"], $this->conf["name_company"]);
			$email->AddAddress($adhesionClient->email);
			$email->Subject=$subject;
			$email->isHTML(true); 
			$email->CharSet="utf-8";
			//$email->AddEmbeddedImage("res/image.png", "CarteAdherent", "image.png");
			$status = false;
			while($status != true) {
				$status = file_exists('res/Carte'. $adhesionClient->id .'.jpg');
				if ($status == true)
					break;
			}
			$email->AddAttachment('res/Carte'. $adhesionClient->id .'.jpg');
			$email->Body=$body;
			$email->Send();
		} catch (phpmailerException $e) { 
			$error=$e->getMessage();
		} catch (Exception $e) {
			$error=$e->getMessage();
		} 

	}

	public function get_models() {
		$r=array();
		$filter=$this->models_dir."/*.html";
		$files=glob($filter);
		foreach($files as $file) {
			array_push($r, basename($file));
		} 
		$filter=$this->models_dir."/*.php";
		$files=glob($filter);
		foreach($files as $file) {
			array_push($r, basename($file));
		} 

		return $r;
	}
}

?>
