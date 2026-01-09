<?php
class AdhesionClient {
	
	public $id;
	public $last_name;
	public $first_name;
	public $email;
	public $adhesion_type;
	public $date_debut;
	public $date_fin;
	public $newsletter;
	public $new;
	
	public function __construct() {
		$this->new = true;
	}
}