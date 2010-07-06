<?php

class Sections_NonIterable_Object extends Mustache {
	public $start = "It worked the first time.";

	public function middle() {
		return new Non_Iterable_Object;
	}

	public $final = "Then, surprisingly, it worked the final time.";
}

class Non_Iterable_Object
{
	protected $_data = array(
		'foo' => 'And it worked the second time.',
		'bar' => 'As well as the third.'
	);

	public function __get($key)
	{
		return isset($this->_data[$key]) ? $this->_data[$key] : NULL;
	}

	public function __isset($key)
	{
		return isset($this->_data[$key]);
	}
}