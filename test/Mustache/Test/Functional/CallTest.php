<?php

namespace Mustache\Test\Functional;

use Mustache\Mustache;

/**
 * @group magic_methods
 * @group functional
 */
class CallTest extends \PHPUnit_Framework_TestCase {

	public function testCallEatsContext() {
		$m = new Mustache;
		$tpl = $m->loadTemplate('{{# foo }}{{ label }}: {{ name }}{{/ foo }}');

		$foo = new ClassWithCall();
		$foo->name = 'Bob';

		$data = array('label' => 'name', 'foo' => $foo);

		$this->assertEquals('name: Bob', $tpl->render($data));
	}
}

class ClassWithCall {
	public $name;
	public function __call($method, $args) {
		return 'unknown value';
	}
}
