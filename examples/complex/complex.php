<?php

class Complex extends Mustache {
	public $header = 'Colors';
	
	public $item = array(
		array('name' => 'red', 'current' => true, 'url' => '#Red'),
		array('name' => 'green', 'current' => false, 'url' => '#Green'),
		array('name' => 'blue', 'current' => false, 'url' => '#Blue'),
	);
	
	public function link() {	
		// Exploit the fact that the current iteration item is at the top of the context stack.
		return $this->getVariable($current) != true;
	}
	
	public function list() {
		return !($this->empty());
	}
	
	public function empty() {
		return count($this->item) === 0;
	}
}