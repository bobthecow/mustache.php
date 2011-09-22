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
		$template = '{{> foo }}{{> bar }}';
		$data = array(
			'truthy' => true,
			'foo'    => 'FOO',
			'bar'    => 'BAR'
		);
		$output = '{{ foo }}{{ bar }}';
		$m = new Mustache();
		$partials = new MustacheLoader(dirname(__FILE__).'/fixtures');
		$m->render($template, $data, $partials);
		$this->assertEquals($output, $output);
	}
}