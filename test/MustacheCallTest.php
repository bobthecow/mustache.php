<?php

require_once '../Mustache.php';

class MustacheCallTest extends PHPUnit_Framework_TestCase {

	public function testCallEatsContext() {
		$foo = new ClassWithCall();
		$foo->name = 'Bob';

		$template = '{{# foo }}{{ label }}: {{ name }}{{/ foo }}';
		$data = array('label' => 'name', 'foo' => $foo);
		$m = new Mustache($template, $data);

		$this->assertEquals('name: Bob', $m->render());
	}
}

class ClassWithCall {
	public $name;
	public function __call($method, $args) {
		return 'unknown value';
	}
}
