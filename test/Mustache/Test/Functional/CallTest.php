<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
