<?php

require_once '../Mustache.php';

/**
 * @group sections
 */
class MustacheObjectSectionTest extends PHPUnit_Framework_TestCase {
	public function testBasicObject() {
		$alpha = new Alpha();
		$this->assertEquals('Foo', $alpha->render('{{#foo}}{{name}}{{/foo}}'));
	}

	public function testObjectWithGet() {
		$beta = new Beta();
		$this->assertEquals('Foo', $beta->render('{{#foo}}{{name}}{{/foo}}'));
	}

	public function testSectionObjectWithGet() {
		$gamma = new Gamma();
		$this->assertEquals('Foo', $gamma->render('{{#bar}}{{#foo}}{{name}}{{/foo}}{{/bar}}'));
	}

	public function testSectionObjectWithFunction() {
		$alpha = new Alpha();
		$alpha->foo = new Delta();
		$this->assertEquals('Foo', $alpha->render('{{#foo}}{{name}}{{/foo}}'));
	}
}

class Alpha extends Mustache {
	public $foo;

	public function __construct() {
		$this->foo = new StdClass();
		$this->foo->name = 'Foo';
		$this->foo->number = 1;
	}
}

class Beta extends Mustache {
	protected $_data = array();

	public function __construct() {
		$this->_data['foo'] = new StdClass();
		$this->_data['foo']->name = 'Foo';
		$this->_data['foo']->number = 1;
	}

	public function __isset($name) {
		return array_key_exists($name, $this->_data);
	}

	public function __get($name) {
		return $this->_data[$name];
	}
}

class Gamma extends Mustache {
	public $bar;

	public function __construct() {
		$this->bar = new Beta();
	}
}

class Delta extends Mustache {
	protected $_name = 'Foo';

	public function name() {
		return $this->_name;
	}
}