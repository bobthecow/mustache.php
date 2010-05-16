<?php

class Partials extends Mustache {
	public $name = 'ilmich';
	public $data = array(
		array('name' => 'federica', 'age' => 27, 'gender' => 'female'),
		array('name' => 'marco', 'age' => 32, 'gender' => 'male'),
	);

	protected $_partials = array(
		'children' => "{{#data}}{{name}} - {{age}} - {{gender}}\n{{/data}}",
	);
}