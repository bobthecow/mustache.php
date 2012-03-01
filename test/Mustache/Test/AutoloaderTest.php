<?php

namespace Mustache\Test;

use Mustache\Autoloader;

/**
 * @group unit
 */
class AutoloaderTest extends \PHPUnit_Framework_TestCase {
	public function testRegister() {
		$loader = Autoloader::register();
		$this->assertTrue(spl_autoload_unregister(array($loader, 'autoload')));
	}

	public function testAutoloader() {
		$loader = new Autoloader(__DIR__.'/../../fixtures/autoloader');

		$this->assertNull($loader->autoload('NonMustacheClass'));
		$this->assertFalse(class_exists('NonMustacheClass'));

		$loader->autoload('Mustache\Foo');
		$this->assertTrue(class_exists('Mustache\Foo'));

		$loader->autoload('\Mustache\Bar');
		$this->assertTrue(class_exists('Mustache\Bar'));
	}
}
