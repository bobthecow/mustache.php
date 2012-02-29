<?php

require_once '../Mustache.php';

class MustacheCallTest extends PHPUnit_Framework_TestCase {

	public function testCallEatsContext() {
		$foo = new Foo();
		$foo->name = 'Bob';

		$template = '{{# foo }}{{ label }}: {{ name }}{{/ foo }}';
		$data = array('label' => 'name', 'foo' => $foo);
		$m = new Mustache($template, $data);

		$this->assertEquals('name: Bob', $m->render());
	}
}

class Foo {
	public $name;
	public function __call($method, $args) {
		return 'unknown value';
	}
}
