<?php

class MailChimpHandler {
	private $conf, $conn;

	public function __construct($conn, $conf) {
		$this->conn=$conn;
		$this->conf=$conf;
	}
	
	public function manageEmailList($adhesion_client, $taglist, $operation) {
		$email = $adhesion_client->email;

		$apiKey = $this->conf['apiKey'];
		$listId = $this->conf['listId'];

		$memberId = md5(strtolower($email));
		$dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
		print $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberId;

		//Member info
		$data = array(
			'email_address'	=>$email,
			'status' 		=> 'subscribed',
			'tags'	 		=> $taglist,
			'merge_fields'  => [
					'FNAME'	=> $adhesion_client->first_name,
					'LNAME'	=> $adhesion_client->last_name
			]
			);
		$jsonString = json_encode($data);

		// send a HTTP POST request with curl
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $operation);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonString);
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		$msg = "";
		//Collecting the status
		switch ($httpCode) {
			case 200:
				$msg = 'Success, newsletter subcribed using mailchimp API';
				break;
			case 204:
				$msg = 'Success, newsletter unsubcribed using mailchimp API';
				break;
			case 214:
				$msg = 'Already Subscribed';
				break;
			default:
					$msg = 'Oops, please try again.[msg_code='.$httpCode.']';
					break;
			}
			echo "mailchimp: ".$msg ."</br>Param - $apiKey=".$apiKey. " - $listId=" . $listId . " - $email=".$email ;

		if ($msg = "")
			return true;	
		else
			return false;
	} 
}
?>
