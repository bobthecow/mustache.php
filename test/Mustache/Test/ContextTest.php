<?php

namespace Mustache\Test;

use Mustache\Context;

/**
 * @group unit
 */
class ContextTest extends \PHPUnit_Framework_TestCase {
	public function testConstructor() {
		$one = new Context;
		$this->assertSame('', $one->find('foo'));
		$this->assertSame('', $one->find('bar'));

		$two = new Context(array(
			'foo' => 'FOO',
			'bar' => '<BAR>'
		));
		$this->assertEquals('FOO', $two->find('foo'));
		$this->assertEquals('<BAR>', $two->find('bar'));

		$obj = new \StdClass;
		$obj->name = 'NAME';
		$three = new Context($obj);
		$this->assertSame($obj, $three->last());
		$this->assertEquals('NAME', $three->find('name'));
	}

	public function testIsTruthy() {
		$context = new Context;

		$this->assertTrue($context->isTruthy('string'));
		$this->assertTrue($context->isTruthy(new \StdClass));
		$this->assertTrue($context->isTruthy(1));
		$this->assertTrue($context->isTruthy(array('a', 'b')));

		$this->assertFalse($context->isTruthy(null));
		$this->assertFalse($context->isTruthy(''));
		$this->assertFalse($context->isTruthy(0));
		$this->assertFalse($context->isTruthy(array()));
	}

	public function testIsCallable() {
		$dummy   = new TestDummy;
		$context = new Context;

		$this->assertTrue($context->isCallable(function() { return null; }));
		$this->assertTrue($context->isCallable(array('\Mustache\Test\TestDummy', 'foo')));
		$this->assertTrue($context->isCallable(array($dummy, 'bar')));
		$this->assertTrue($context->isCallable($dummy));

		$this->assertFalse($context->isCallable('count'));
		$this->assertFalse($context->isCallable('TestDummy::foo'));
		$this->assertFalse($context->isCallable(array('\Mustache\Test\TestDummy', 'name')));
		$this->assertFalse($context->isCallable(array('NotReallyAClass', 'foo')));
		$this->assertFalse($context->isCallable(array($dummy, 'name')));
	}

	/**
	 * @dataProvider getIterables
	 */
	public function testIsIterable($value, $iterable) {
		$context = new Context;
		$this->assertEquals($iterable, $context->isIterable($value));
	}

	public function getIterables() {
		return array(
			array(array(0 => 'a', 1 => 'b'), true),
			array(array(0 => 'a', 2 => 'b'), false),
			array(array(1 => 'a', 2 => 'b'), false),
			array(array('a' => 0, 'b' => 1), false),
			array('some string',             false),
			array(new \ArrayIterator,        true),
		);
	}

	public function testPushPopAndLast() {
		$context = new Context;
		$this->assertFalse($context->last());

		$dummy = new TestDummy;
		$context->push($dummy);
		$this->assertSame($dummy, $context->last());
		$this->assertSame($dummy, $context->pop());
		$this->assertFalse($context->last());

		$obj = new \StdClass;
		$context->push($dummy);
		$this->assertSame($dummy, $context->last());
		$context->push($obj);
		$this->assertSame($obj, $context->last());
		$this->assertSame($obj, $context->pop());
		$this->assertSame($dummy, $context->pop());
		$this->assertFalse($context->last());
	}

	public function testFind() {
		$context = new Context;

		$dummy = new TestDummy;

		$obj = new \StdClass;
		$obj->name = 'obj';

		$arr = array(
			'a' => array('b' => array('c' => 'see')),
			'b' => 'bee',
		);

		$string = 'some arbitrary string';

		$context->push($dummy);
		$this->assertEquals('dummy', $context->find('name'));

		$context->push($obj);
		$this->assertEquals('obj', $context->find('name'));

		$context->pop();
		$this->assertEquals('dummy', $context->find('name'));

		$dummy->name = 'dummyer';
		$this->assertEquals('dummyer', $context->find('name'));

		$context->push($arr);
		$this->assertEquals('bee', $context->find('b'));
		$this->assertEquals('see', $context->findDot('a.b.c'));

		$dummy->name = 'dummy';

		$context->push($string);
		$this->assertSame($string, $context->last());
		$this->assertEquals('dummy', $context->find('name'));
		$this->assertEquals('see', $context->findDot('a.b.c'));
		$this->assertEquals('<foo>', $context->find('foo'));
		$this->assertEquals('<bar>', $context->findDot('bar'));
	}
}

class TestDummy {
	public $name = 'dummy';
	public function __invoke() {}
	public static function foo() {
		return '<foo>';
	}
	public function bar() {
		return '<bar>';
	}
}
