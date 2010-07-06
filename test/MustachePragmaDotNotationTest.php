<?php

require_once '../Mustache.php';

class MustachePragmaDotNotationTest extends PHPUnit_Framework_TestCase {

	public function testDotTraversal() {
		$m = new Mustache('', array('foo' => array('bar' => 'this worked')));

		$this->assertEquals($m->render('{{foo.bar}}'), '',
			'Dot notation not enabled, variable should have been replaced with nothing');
		$this->assertEquals($m->render('{{%DOT-NOTATION}}{{foo.bar}}'), 'this worked',
			'Dot notation enabled, variable should have been replaced by "this worked"');
	}

	public function testDeepTraversal() {
		$data = array(
			'foo' => array('bar' => array('baz' => array('qux' => array('quux' => 'WIN!')))),
			'a' => array('b' => array('c' => array('d' => array('e' => 'abcs')))),
			'one' => array(
				'one'   => 'one-one',
				'two'   => 'one-two',
				'three' => 'one-three',
			),
		);

		$m = new Mustache('', $data);
		$this->assertEquals($m->render('{{%DOT-NOTATION}}{{foo.bar.baz.qux.quux}}'), 'WIN!');
		$this->assertEquals($m->render('{{%DOT-NOTATION}}{{a.b.c.d.e}}'), 'abcs');
		$this->assertEquals($m->render('{{%DOT-NOTATION}}{{one.one}}|{{one.two}}|{{one.three}}'), 'one-one|one-two|one-three');
	}

	public function testDotNotationContext() {
		$data = array('parent' => array('items' => array(
			array('item' => array('index' => 1)),
			array('item' => array('index' => 2)),
			array('item' => array('index' => 3)),
			array('item' => array('index' => 4)),
			array('item' => array('index' => 5)),
		)));

		$m = new Mustache('', $data);
		$this->assertEquals('12345', $m->render('{{%DOT-NOTATION}}{{#parent}}{{#items}}{{item.index}}{{/items}}{{/parent}}'));
	}

	public function testDotNotationSectionNames() {
		$data = array('parent' => array('items' => array(
			array('item' => array('index' => 1)),
			array('item' => array('index' => 2)),
			array('item' => array('index' => 3)),
			array('item' => array('index' => 4)),
			array('item' => array('index' => 5)),
		)));

		$m = new Mustache('', $data);
		$this->assertEquals('.....', $m->render('{{%DOT-NOTATION}}{{#parent.items}}.{{/parent.items}}'));
		$this->assertEquals('12345', $m->render('{{%DOT-NOTATION}}{{#parent.items}}{{item.index}}{{/parent.items}}'));
		$this->assertEquals('12345', $m->render('{{%DOT-NOTATION}}{{#parent.items}}{{#item}}{{index}}{{/item}}{{/parent.items}}'));
	}
}