<?php

class PartialsWithViewClass extends Mustache {
	public function __construct($template = null, $view = null, $partials = null) {
		// Use an object of an arbitrary class as a View for this Mustache instance:
		$view = new StdClass();
		$view->name = 'ilmich';
		$view->data = array(
			array('name' => 'federica', 'age' => 27, 'gender' => 'female'),
			array('name' => 'marco', 'age' => 32, 'gender' => 'male'),
		);

		$partials = array(
			'children' => "{{#data}}{{name}} - {{age}} - {{gender}}\n{{/data}}",
		);

		parent::__construct($template, $view, $partials);
	}
}