<?php

class RecursivePartials extends Mustache {
	protected $_partials = array(
		'child' => "* {{ name }}",
	);

	public $name  = 'George';
	public $child = array(
		'name' => 'Dan',
		'child' => array(
			'name' => 'Justin',
		)
	);
}