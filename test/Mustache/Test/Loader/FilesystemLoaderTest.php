<?php

namespace Mustache\Test\Loader;

use Mustache\Loader\FilesystemLoader;

/**
 * @group unit
 */
class FilesystemLoaderTest extends \PHPUnit_Framework_TestCase {
	public function testConstructor() {
		$baseDir = realpath(__DIR__.'/../../../fixtures/templates');
		$loader = new FilesystemLoader($baseDir, array('extension' => '.ms'));
		$this->assertEquals('alpha contents', $loader->load('alpha'));
		$this->assertEquals('beta contents', $loader->load('beta.ms'));
	}

	public function testLoadTemplates() {
		$baseDir = realpath(__DIR__.'/../../../fixtures/templates');
		$loader = new FilesystemLoader($baseDir);
		$this->assertEquals('one contents', $loader->load('one'));
		$this->assertEquals('two contents', $loader->load('two.mustache'));
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testMissingBaseDirThrowsException() {
		$loader = new FilesystemLoader(__DIR__.'/not_a_directory');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testMissingTemplateThrowsException() {
		$baseDir = realpath(__DIR__.'/../../../fixtures/templates');
		$loader = new FilesystemLoader($baseDir);

		$loader->load('fake');
	}
}
