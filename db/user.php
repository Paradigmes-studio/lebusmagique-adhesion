<?php
class User {
	public $login = null;
	public $password;
	public $new;
	
	public function __construct() {
		$this->new = true;
	}
	public function set_password($password) {
		$this->password=password_hash($password, PASSWORD_DEFAULT);
	}
}