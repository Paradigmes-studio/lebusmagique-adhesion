<?php

require_once("db/mailchimpTag.php");
require_once('lib/utils.php');

class mMailchimpTag {
	private $conn, $conf;

	public function __construct($conn, $conf) {
		$this->conn=$conn;
		$this->conf=$conf;
	}

	public function read($id, $mailchimp_tag) {
		$query = $this->conn->prepare("SELECT * FROM adh_mailchimp_tag WHERE id = :id");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
		
		if ($res=$query->fetch()) {
			$mailchimp_tag->id = (int)$res['id'];
			$mailchimp_tag->name = $res['name'];
			$mailchimp_tag->active = $res['active'];

			$mailchimp_tag->new = false;

			return true;
		} else {
			return false;
		}
	}

	public function get_count() {
		$query=$this->conn->query("SELECT COUNT(1) FROM adh_mailchimp_tag;");
		$query->execute();
		return $query->fetch()[0];
	}

	public function write($mailchimp_tag) {
		
		if ($this->conn->inTransaction()) {
			$in_transaction = true;
		} else {
			$this->conn->beginTransaction();
			$in_transaction = false;
		} 
		
		if ($mailchimp_tag->new) {
			$query = $this->conn->prepare("INSERT INTO adh_mailchimp_tag(name, active) VALUES (:name, :active)");
		} else {
			$query = $this->conn->prepare("UPDATE adh_mailchimp_tag SET name = :name, active = :active WHERE id = :id;");
			$query->bindValue(':id', $mailchimp_tag->id, PDO::PARAM_INT);
		}
		$query->bindValue(':name', $mailchimp_tag->name, PDO::PARAM_STR);
		$query->bindValue(':active', $mailchimp_tag->active, PDO::PARAM_INT);
		
		$query->execute();
		if ($mailchimp_tag->new) {
			$mailchimp_tag->id=(int)$this->conn->lastInsertId(); // ?
		} 

		$mailchimp_tag->new = false; 
		if (!$in_transaction) {
			$this->conn->commit();
		}
	}

	public function list_mailchimp_tag() {
		$query=$this->conn->prepare("SELECT * FROM adh_mailchimp_tag ORDER BY name");
		$query->execute();
		$mailchimp_tags=array();
		while ($res=$query->fetch()) {
			$mailchimp_tag			= new MailchimpTag();
			$mailchimp_tag->id		= $res['id'];
			$mailchimp_tag->name	= $res['name'];
			$mailchimp_tag->active 	= $res['active'];
			$mailchimp_tag->new=false;
			array_push($mailchimp_tags, $mailchimp_tag);
		} 
		return $mailchimp_tags;
	} 

	public function list_mailchimp_tag_name() {
		$query=$this->conn->prepare("SELECT name FROM adh_mailchimp_tag where active = true ORDER BY name");
		$query->execute();
		$mailchimp_tags=array();
		while ($res=$query->fetch()) {
			array_push($mailchimp_tags, $res['name']);
		} 
		return $mailchimp_tags;
	} 

	public function get_mailchimp_tag($id) {
		$query=$this->conn->prepare("SELECT name FROM adh_mailchimp_tag WHERE id=:id;");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
		if (!($res=$query->fetch())) {
			throw new Exception(sprintf("the mailchimp tag with id %d doesn't exists", $id));
		}
		return $res['name'];
	}

	public function delete_by_name($name) {
		$query=$this->conn->prepare("DELETE FROM adh_mailchimp_tag WHERE name=:name");
		$query->bindValue(":name", $name, PDO::PARAM_STR);
		$query->execute();
	}

	public function delete_by_id($id) {
		$query=$this->conn->prepare("DELETE FROM adh_mailchimp_tag WHERE id=:id");
		$query->bindValue(":id", $id, PDO::PARAM_INT);
		$query->execute();
	} 

	public function get_mailchimp_tag_id_by_name($name) {
		$query=$this->conn->prepare("SELECT id FROM adh_mailchimp_tag WHERE name = :name;");
		$query->bindValue(":name", $name, PDO::PARAM_STR);
		$query->execute();
		if (!($res=$query->fetch())) {
			throw new Exception(sprintf("the mailchimp tag with name %s doesn't exists", $name));
		}
		return $res[0]; 
	}

	public function read_by_name($name, $mailchimp_tag) {
		$id = $this->get_mailchimp_tag_id_by_name($name);
		$this->read($id, $adhesion_type);
	}
	
	public function get_tags() {
		// used to display exceptons in the tour_type form
		$query=$this->conn->prepare("SELECT * FROM adh_mailchimp_tag ORDER BY name");
		$query->execute();
		$mailchimp_tags=array();
		while ($res=$query->fetch()) {
			$mailchimp_tag = (object) [];
			$mailchimp_tag->id		= $res['id'];
			$mailchimp_tag->name	= $res['name'];
			$mailchimp_tag->active 	= $res['active'];
			array_push($mailchimp_tags, $mailchimp_tag);
		} 
		return json_encode($mailchimp_tags);

	} 
}


