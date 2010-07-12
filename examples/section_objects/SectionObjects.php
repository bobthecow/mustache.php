<?php

class SectionObjects extends Mustache {
	public $start = "It worked the first time.";

	public function middle() {
		return new SectionObject;
	}

	public $final = "Then, surprisingly, it worked the final time.";
}

class SectionObject {
	public $foo = 'And it worked the second time.';
	public $bar = 'As well as the third.';
}