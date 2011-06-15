<?php

require_once '../Mustache.php';

/**
 * @group pragmas
 */
class MustachePipeTest extends PHPUnit_Framework_TestCase {

	public function testObjects() {
		$data = array('foo' => (object)array('bar' => 'baz'));
		$template = '{{|foo}}';
		$output = '{"bar":"baz"}';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}
	
	public function testArrays() {
		$data = array('foo' => array('bar' => 'baz'));
		$template = '{{|foo}}';
		$output = '{"bar":"baz"}';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}
	
	public function testStrings() {
		$data = array('foo' => 'bar');
		$template = '{{|foo}}';
		$output = '["bar"]';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}
	
	public function testLists() {
		$data = array('foo' => array('bar','baz'));
		$template = '{{|foo}}';
		$output = '["bar","baz"]';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}
	
	public function testFalsy() {
		$data = array('foo' => false);
		$template = '{{|foo}}';
		$output = '[]';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}
	
	public function testUnrenderables() {
		$data = array('foo' => function(){return "bar";});
		$template = '{{|foo}}';
		$output = '[]';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}

}