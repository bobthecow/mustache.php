<?php

class Simple extends Mustache {
	public $name = "Chris";
	public $last_name = '"Bob"';
	public $value = 10000;

	public function taxed_value() {
		return $this->value - ($this->value * 0.4);
	}

	public $in_ca = true;
};