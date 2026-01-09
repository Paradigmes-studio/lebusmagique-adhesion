<?php

#global $conf;
#if ($conf['debug']) {
#}

class Debug {
	public function __construct($conn, $conf) {
		$this->conn = $conn;
		$this->conf = $conf;
	}

	private function get_current_id() {
		$query=$this->conn->prepare("SELECT id FROM adh_debug WHERE in_progress");
		$query->execute();
		if ($res = $query->fetch()) 
		{
			return $res['id'];
		} else {
			return -1;
		} 
	}

	private function get_last_id() {
		$query=$this->conn->prepare("SELECT max(id) AS id FROM adh_debug");
		$query->execute();
		if ($res = $query->fetch()) 
		{
			return $res['id'];
		} else {
			return -1;
		} 
	} 

	public function in_progress() {
		$a = $this->get_current_id() != -1;
		return $a;
	}

	public function start() {
		if ($this->in_progress()) {
			throw new Exception('a debug is already in progres');
		} else {
			$query = $this->conn->prepare("INSERT INTO adh_debug(in_progress) VALUES (True);");
			$query->execute(); 
		} 
	}

	public function stop() {
		$query = $this->conn->prepare("UPDATE adh_debug SET in_progress = FALSE");
		$query->execute(); 
	}

	public function log_() {
# if enabled, log current GET and POST query
# also, only if conf says dev = true. first it's another protection, second it will be faster, avoiding an extra query every time
		if ($this->conf['dev']) {
			if (!(strpos($_SERVER['PHP_SELF'], 'debug.php'))) { 
				$id = $this->get_current_id();
				if ($id != -1) {
					$query = $this->conn->prepare("INSERT INTO adh_debug_detail(debug, request, get_, post_) VALUES (:debug, :request, :get, :post);");
					$query->bindValue(":debug", $id, PDO::PARAM_INT);
					$query->bindValue(":request", json_encode(basename($_SERVER['PHP_SELF'])), PDO::PARAM_STR);
					$query->bindValue(":get", json_encode($_GET), PDO::PARAM_STR);
					$query->bindValue(":post", json_encode($_POST), PDO::PARAM_STR);
					$query->execute();
				}
			}
		}
	}

	public function get_log() {
		$id = $this->get_last_id();
		$query = $this->conn->prepare("SELECT request, get_, post_ FROM  adh_debug_detail WHERE debug = :debug ORDER by id;");
		$query->bindValue(":debug", $id, PDO::PARAM_INT);
		$query->execute(); 
		$a = array();
		while ($res = $query->fetch()) {
			$b = array();
			$b['request'] = json_decode($res['request']);
			$b['get'] = json_decode($res['get']);
			$b['post'] = json_decode($res['post']);
			array_push($a, $b);
		}
		return $a; 
	}
}
