<?php

require_once("db/adhesionClient.php");
require_once('lib/utils.php');

class mAdhesionClient {
	private $conn, $conf;

	public function __construct($conn, $conf) {
		$this->conn=$conn;
		$this->conf=$conf;
	}

	public function read($id, $adhesion_client) {
		$query = $this->conn->prepare("SELECT * FROM adh_adhesion_client WHERE id = :id");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
		
		if ($res=$query->fetch()) {
			$adhesion_client->id = (int)$res['id'];
			$adhesion_client->last_name = $res['last_name'];
			$adhesion_client->first_name = $res['first_name'];
			$adhesion_client->email = $res['email'];
			$adhesion_client->adhesion_type = $res['adhesion_type'];
			$adhesion_client->date_debut = $res['date_debut'];
			$adhesion_client->date_fin = $res['date_fin'];
			$adhesion_client->newsletter = $res['newsletter'];
			$adhesion_client->new = false;

			return true;
		} else {
			return false;
		}
	}
	
	public function search($where) {
		$query = $this->conn->prepare("SELECT * FROM adh_adhesion_client " . $where);
		$query->execute();
		
		$adhesion_clients=array();
		while ($res=$query->fetch()) {
			$adhesion_client=new adhesionClient();
			$adhesion_client->id = (int)$res['id'];
			$adhesion_client->last_name = $res['last_name'];
			$adhesion_client->first_name = $res['first_name'];
			$adhesion_client->email = $res['email'];
			$adhesion_client->adhesion_type = $res['adhesion_type'];
			$adhesion_client->date_debut = $res['date_debut'];
			$adhesion_client->date_fin = $res['date_fin'];
			$adhesion_client->newsletter = $res['newsletter'];
			$adhesion_client->new = false;

			array_push($adhesion_clients, $adhesion_client);
		} 
		return $adhesion_clients;
	}


	public function get_count() {
		$query=$this->conn->query("SELECT COUNT(1) FROM adh_adhesion_client;");
		$query->execute();
		return $query->fetch()[0];
	}

	public function write($adhesion_client) {
		
		if ($this->conn->inTransaction()) {
			$in_transaction = true;
		} else {
			$this->conn->beginTransaction();
			$in_transaction = false;
		} 
		
		if ($adhesion_client->new) {
			$query = $this->conn->prepare("INSERT INTO adh_adhesion_client(last_name, first_name, email, adhesion_type, date_debut, date_fin, newsletter) VALUES (:last_name, :first_name, :email, :adhesion_type, :date_debut, :date_fin, :newsletter)");
		} else {
			$query = $this->conn->prepare("UPDATE adh_adhesion_client SET last_name = :last_name, first_name = :first_name, email = :email, adhesion_type = :adhesion_type, date_debut = :date_debut, date_fin = :date_fin, newsletter = :newsletter WHERE id = :id;");
			$query->bindValue(':id', $adhesion_client->id, PDO::PARAM_INT);
		}
		$query->bindValue(':last_name', $adhesion_client->last_name, PDO::PARAM_STR);
		$query->bindValue(':first_name', $adhesion_client->first_name, PDO::PARAM_STR);
		$query->bindValue(':email', $adhesion_client->email, PDO::PARAM_STR);
		$query->bindValue(':adhesion_type', $adhesion_client->adhesion_type, PDO::PARAM_STR);
		$query->bindValue(':date_debut', $adhesion_client->date_debut, PDO::PARAM_STR);
		$query->bindValue(':date_fin', $adhesion_client->date_fin, PDO::PARAM_STR);
		$query->bindValue(':newsletter', $adhesion_client->newsletter, PDO::PARAM_INT);

		$query->execute();
		if ($adhesion_client->new) {
			$adhesion_client->id=(int)$this->conn->lastInsertId(); // ?
		} 

		$adhesion_client->new = false; 
		if (!$in_transaction) {
			$this->conn->commit();
		}
	}

	public function list_adhesion_client() {
		$query=$this->conn->prepare("SELECT * FROM adh_adhesion_client ORDER BY last_name");
		$query->execute();
		$adhesion_clients=array();
		while ($res=$query->fetch()) {
			$adhesion_client=new adhesionClient();
			$adhesion_client->id = (int)$res['id'];
			$adhesion_client->last_name = $res['last_name'];
			$adhesion_client->first_name = $res['first_name'];
			$adhesion_client->email = $res['email'];
			$adhesion_client->adhesion_type = $res['adhesion_type'];
			$adhesion_client->date_debut = $res['date_debut'];
			$adhesion_client->date_fin = $res['date_fin'];
			$adhesion_client->newsletter = $res['newsletter'];
			$adhesion_client->new = false;
			array_push($adhesion_clients, $adhesion_client);
		} 
		return $adhesion_clients;
	} 

	public function get_adhesion_client_name($id) {
		$query=$this->conn->prepare("SELECT name FROM adh_adhesion_client WHERE id=:id;");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
		if (!($res=$query->fetch())) {
			throw new Exception(sprintf("the adhesion type with id %d doesn't exists", $id));
		}
		return $res['name'];
	}

	public function delete_by_id($id) {
		$query=$this->conn->prepare("DELETE FROM adh_adhesion_client WHERE id=:id");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
	} 

	public function get_adhesion_client_id_by_name($name) {
		$query=$this->conn->prepare("SELECT id FROM adh_adhesion_client WHERE name = :name;");
		$query->bindValue(":name", $name, PDO::PARAM_STR);
		$query->execute();
		if (!($res=$query->fetch())) {
			throw new Exception(sprintf("the adhesion type with name %s doesn't exists", $name));
		}
		return $res[0]; 
	}

	public function read_by_name($name, $adhesion_client) {
		$id = $this->get_adhesion_client_id_by_name($name);
		$this->read($id, $adhesion_client);
	}
	
	public function get_used_in_adhesion_client($id) {
		$query=$this->conn->prepare("SELECT count(1) FROM adh_adhesion_client WHERE adhesion_client=:id;");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
		$res=$query->fetch();
		return ($res[0]>0);
	} 
}


