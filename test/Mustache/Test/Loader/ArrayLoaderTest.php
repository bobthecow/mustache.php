<?php

namespace Mustache\Test\Loader;

use Mustache\Loader\ArrayLoader;

/**
 * @group unit
 */
class ArrayLoaderTest extends \PHPUnit_Framework_TestCase {
	public function testConstructor() {
		$loader = new ArrayLoader(array(
			'foo' => 'bar'
		));

		$this->assertEquals('bar', $loader->load('foo'));
	}

	public function testSetAndLoadTemplates() {
		$loader = new ArrayLoader(array(
			'foo' => 'bar'
		));
		$this->assertEquals('bar', $loader->load('foo'));

		$loader->setTemplate('baz', 'qux');
		$this->assertEquals('qux', $loader->load('baz'));

		$loader->setTemplates(array(
			'foo' => 'FOO',
			'baz' => 'BAZ',
		));
		$this->assertEquals('FOO', $loader->load('foo'));
		$this->assertEquals('BAZ', $loader->load('baz'));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testMissingTemplatesThrowExceptions() {
		$loader = new ArrayLoader;
		$loader->load('not_a_real_template');
	}
}
