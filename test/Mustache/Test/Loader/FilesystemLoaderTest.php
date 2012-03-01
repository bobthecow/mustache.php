<?php

require_once '../Mustache.php';
require_once '../MustacheLoader.php';

/**
 * @group loader
 */
class MustacheLoaderTest extends PHPUnit_Framework_TestCase {

	public function testTheActualFilesystemLoader() {
		$loader = new MustacheLoader(dirname(__FILE__).'/fixtures');
		$this->assertEquals(file_get_contents(dirname(__FILE__).'/fixtures/foo.mustache'), $loader['foo']);
		$this->assertEquals(file_get_contents(dirname(__FILE__).'/fixtures/bar.mustache'), $loader['bar']);
	}

	public function testMustacheUsesFilesystemLoader() {
		$template = '{{> foo }} {{> bar }}';
		$data = array(
			'truthy' => true,
			'foo'    => 'FOO',
			'bar'    => 'BAR',
		);
		$output = 'FOO BAR';
		$m = new Mustache();
		$partials = new MustacheLoader(dirname(__FILE__).'/fixtures');
		$this->assertEquals($output, $m->render($template, $data, $partials));
	}

	public function testMustacheUsesDifferentLoadersToo() {
		$template = '{{> foo }} {{> bar }}';
		$data = array(
			'truthy' => true,
			'foo'    => 'FOO',
			'bar'    => 'BAR',
		);
		$output = 'FOO BAR';
		$m = new Mustache();
		$partials = new DifferentMustacheLoader();
		$this->assertEquals($output, $m->render($template, $data, $partials));
	}
}

class DifferentMustacheLoader implements ArrayAccess {
	protected $partials = array(
		'foo' => '{{ foo }}',
		'bar' => '{{# truthy }}{{ bar }}{{/ truthy }}',
	);

	public function offsetExists($offset) {
		return isset($this->partials[$offset]);
	}

	public function offsetGet($offset) {
		return $this->partials[$offset];
	}

	public function offsetSet($offset, $value) {}
	public function offsetUnset($offset) {}
}
