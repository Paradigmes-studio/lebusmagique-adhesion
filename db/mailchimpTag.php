<?php
class MailchimpTag {
	
	public $id;
	public $name;
	public $active;
	public $new;
	
	public function __construct() {
		$this->new = true;
	}
}