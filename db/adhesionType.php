<?php
class AdhesionType {
	
	public $id;
	public $name;
	public $price;
	public $email_welcome;
	public $duration;
	public $new;
	
	public function __construct() {
		$this->new = true;
	}
}