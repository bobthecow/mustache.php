<?php

namespace Mustache\Test\Loader;

use Mustache\Loader\StringLoader;

/**
 * @group unit
 */
class StringLoaderTest extends \PHPUnit_Framework_TestCase {
	public function testLoadTemplates() {
		$loader = new StringLoader;

		$this->assertEquals('foo', $loader->load('foo'));
		$this->assertEquals('{{ bar }}', $loader->load('{{ bar }}'));
		$this->assertEquals("\n{{! comment }}\n", $loader->load("\n{{! comment }}\n"));
	}
}
