<?php

class RecursivePartials extends Mustache {
	protected $_partials = array(
		'child' => "* {{ name }}\n{{#child}}{{>child}}\n{{/child}}",
	);

	public $name  = 'George';
	public $child = array(
		'name'  => 'Dan',
		'child' => array(
			'name'  => 'Justin',
			'child' => false,
		)
	);
}