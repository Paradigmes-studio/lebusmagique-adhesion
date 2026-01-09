<?php

require_once("db/adhesionType.php");
require_once('lib/utils.php');

class mAdhesionType {
	private $conn, $conf;

	public function __construct($conn, $conf) {
		$this->conn=$conn;
		$this->conf=$conf;
	}

	public function read($id, $adhesion_type) {
		$query = $this->conn->prepare("SELECT * FROM adh_adhesion_type WHERE id = :id");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
		
		if ($res=$query->fetch()) {
			$adhesion_type->id = (int)$res['id'];
			$adhesion_type->name = $res['name'];
			$adhesion_type->price = floatval($res['price']);
			$adhesion_type->email_welcome = $res['email_welcome'];
			$adhesion_type->duration = read_int($res['duration']);

			$adhesion_type->new = false;

			return true;
		} else {
			return false;
		}
	}

	public function get_count() {
		$query=$this->conn->query("SELECT COUNT(1) FROM adh_adhesion_type;");
		$query->execute();
		return $query->fetch()[0];
	}

	public function write($adhesion_type) {
		
		if ($this->conn->inTransaction()) {
			$in_transaction = true;
		} else {
			$this->conn->beginTransaction();
			$in_transaction = false;
		} 
		
		if ($adhesion_type->new) {
			$query = $this->conn->prepare("INSERT INTO adh_adhesion_type(name, price, email_welcome, duration) VALUES (:name, :price, :email_welcome, :duration)");
		} else {
			$query = $this->conn->prepare("UPDATE adh_adhesion_type SET name = :name, price = :price, email_welcome = :email_welcome, duration = :duration WHERE id = :id;");
			$query->bindValue(':id', $adhesion_type->id, PDO::PARAM_INT);
		}
		$query->bindValue(':name', $adhesion_type->name, PDO::PARAM_STR);
		$query->bindValue(':price', $adhesion_type->price, PDO::PARAM_STR);
		$query->bindValue(':email_welcome', $adhesion_type->email_welcome, PDO::PARAM_STR);
		$query->bindValue(':duration', $adhesion_type->duration, PDO::PARAM_STR);
		
		$query->execute();
		if ($adhesion_type->new) {
			$adhesion_type->id=(int)$this->conn->lastInsertId(); // ?
		} 

		$adhesion_type->new = false; 
		if (!$in_transaction) {
			$this->conn->commit();
		}
	}

	public function list_adhesion_type() {
		$query=$this->conn->prepare("SELECT * FROM adh_adhesion_type ORDER BY name");
		$query->execute();
		$adhesion_types=array();
		while ($res=$query->fetch()) {
			$adhesion_type=new adhesionType();
			$adhesion_type->id=$res['id'];
			$adhesion_type->name=$res['name'];
			$adhesion_type->price = floatval($res['price']);
			$adhesion_type->email_welcome = $res['email_welcome'];
			$adhesion_type->duration = read_int($res['duration']);
			$adhesion_type->new=false;
			array_push($adhesion_types, $adhesion_type);
		} 
		return $adhesion_types;
	} 

	public function get_adhesion_type_name($id) {
		$query=$this->conn->prepare("SELECT name FROM adh_adhesion_type WHERE id=:id;");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
		if (!($res=$query->fetch())) {
			throw new Exception(sprintf("the adhesion type with id %d doesn't exists", $id));
		}
		return $res['name'];
	}

	public function delete_by_name($name) {
		$query=$this->conn->prepare("DELETE FROM adh_adhesion_type WHERE name=:name");
		$query->bindValue(":name", $name, PDO::PARAM_STR);
		$query->execute();
	}

	public function delete_by_id($id) {
		$query=$this->conn->prepare("DELETE FROM adh_adhesion_type WHERE id=:id");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
	} 

	public function get_adhesion_type_id_by_name($name) {
		$query=$this->conn->prepare("SELECT id FROM adh_adhesion_type WHERE name = :name;");
		$query->bindValue(":name", $name, PDO::PARAM_STR);
		$query->execute();
		if (!($res=$query->fetch())) {
			throw new Exception(sprintf("the adhesion type with name %s doesn't exists", $name));
		}
		return $res[0]; 
	}

	public function read_by_name($name, $adhesion_type) {
		$id = $this->get_adhesion_type_id_by_name($name);
		$this->read($id, $adhesion_type);
	}
	
	public function get_used_in_adhesion_client($id) {
		$query=$this->conn->prepare("SELECT count(1) FROM adh_adhesion_client WHERE adhesion_type=:id;");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
		$res=$query->fetch();
		return ($res[0]>0);
	} 
}


