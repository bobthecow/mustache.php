<?php

class Sections_Iterable_Object extends Mustache {
	public $start = "It worked the first time.";

	public function middle() {
		return new Iterable_Object;
	}

	public $final = "Then, surprisingly, it worked the final time.";
}

class Iterable_Object
{
	public $foo = 'And it worked the second time.';
	public $bar = 'As well as the third.';
}