<?php

class Complex extends Mustache {
	public $header = 'Colors';

	public $item = array(
		array('name' => 'red', 'current' => true, 'url' => '#Red'),
		array('name' => 'green', 'current' => false, 'url' => '#Green'),
		array('name' => 'blue', 'current' => false, 'url' => '#Blue'),
	);

	public function isLink() {
		// Exploit the fact that the current iteration item is at the top of the context stack.
		return $this->getVariable('current', $this->context) != true;
	}

	public function notEmpty() {
		return !($this->isEmpty());
	}

	public function isEmpty() {
		return count($this->item) === 0;
	}
}