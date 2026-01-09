<?php

require_once('db/user.php');

class mUser {
	private $conn, $conf;

	public function __construct($conn, $conf) {
		$this->conn=$conn;
		$this->conf=$conf;
	}

	private function read_from_dataset($user, $dataset) {
		$user->login = $dataset['login'];
		$user->password = $dataset['password'];
		$user->new = false;
	}

	public function read($login, $user) {
		$query=$this->conn->prepare("SELECT * FROM adh_user WHERE login = :login");
		$query->bindValue(":login", $login, PDO::PARAM_STR);
		$query->execute();
		if ($res=$query->fetch()) {
			$this->read_from_dataset($user, $res);
			return true;
		} else {
			return false;
		}
	}

	public function get_count() {
		$query=$this->conn->query("SELECT COUNT(1) FROM adh_user;");
		$query->execute();
		return $query->fetch()[0];
	} 

	public function write($user) {
		if ($user->new) {
			$query=$this->conn->prepare("INSERT INTO adh_user (login, password) VALUES (:login, :password)");
					} else {
			$query=$this->conn->prepare("UPDATE adh_user SET password = :password WHERE login = :login;");
		}
		$query->bindValue(':login', $user->login, PDO::PARAM_STR);
		$query->bindValue(':password', $user->password, PDO::PARAM_STR);

		$query->execute();
	} 

	public function list_users() {
		$query=$this->conn->prepare("SELECT * FROM adh_user");
		$query->execute();
		$users=array();
		while ($res = $query->fetch()) {
			$user = new User(); 
			$this->read_from_dataset($user, $res);
			array_push($users, $user);
		} 
		return $users;
	} 

	public function list_users_without($login) {
		$query=$this->conn->prepare("SELECT * FROM adh_user WHERE login != :login ORDER BY LOGIN");
		$query->bindValue(":login", $login, PDO::PARAM_STR);
		$query->execute();
		$users=array();
		while ($res=$query->fetch()) {
			$user = new User();
			$this->read_from_dataset($user, $res);
			array_push($users, $user);
		} 
		return $users;
	} 

	public function delete_by_login($login) {
		$query=$this->conn->prepare("DELETE FROM adh_user WHERE login=:login");
		$query->bindValue(":login", $login, PDO::PARAM_STR);
		$query->execute();
	}
}