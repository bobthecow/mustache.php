<?php

class SectionIteratorObjects extends Mustache {
	public $start = "It worked the first time.";

	protected $_data = array(
		array('item' => 'And it worked the second time.'),
		array('item' => 'As well as the third.'),
	);

	public function middle() {
		return new ArrayIterator($this->_data);
	}

	public $final = "Then, surprisingly, it worked the final time.";
}