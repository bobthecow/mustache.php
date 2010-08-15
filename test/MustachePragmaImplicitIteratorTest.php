<?php

require_once '../Mustache.php';

class MustachePragmaImplicitIteratorTest extends PHPUnit_Framework_TestCase {

	public function testEnablePragma() {
		$m = $this->getMock('Mustache', array('_renderPragma'), array('{{%IMPLICIT-ITERATOR}}'));
		$m->expects($this->exactly(1))
			->method('_renderPragma')
			->with(array('{{%IMPLICIT-ITERATOR}}', 'IMPLICIT-ITERATOR', null));
		$m->render();
	}

	public function testImplicitIterator() {
		$m1 = new Mustache('{{%IMPLICIT-ITERATOR}}{{#items}}{{.}}{{/items}}', array('items' => array('a', 'b', 'c')));
		$this->assertEquals('abc', $m1->render());

		$m2 = new Mustache('{{%IMPLICIT-ITERATOR}}{{#items}}{{.}}{{/items}}', array('items' => array(1, 2, 3)));
		$this->assertEquals('123', $m2->render());
	}

	public function testDotNotationCollision() {
		$m = new Mustache(null, array('items' => array('foo', 'bar', 'baz')));

		$this->assertEquals('foobarbaz', $m->render('{{%IMPLICIT-ITERATOR}}{{%DOT-NOTATION}}{{#items}}{{.}}{{/items}}'));
		$this->assertEquals('foobarbaz', $m->render('{{%DOT-NOTATION}}{{%IMPLICIT-ITERATOR}}{{#items}}{{.}}{{/items}}'));
	}

	public function testCustomIterator() {
		$m = new Mustache(null, array('items' => array('foo', 'bar', 'baz')));

		$this->assertEquals('foobarbaz', $m->render('{{%IMPLICIT-ITERATOR}}{{#items}}{{.}}{{/items}}'));
		$this->assertEquals('foobarbaz', $m->render('{{%IMPLICIT-ITERATOR iterator=i}}{{#items}}{{i}}{{/items}}'));
		$this->assertEquals('foobarbaz', $m->render('{{%IMPLICIT-ITERATOR iterator=items}}{{#items}}{{items}}{{/items}}'));
	}

	public function testDotNotationContext() {
		$m = new Mustache(null, array('items' => array(
			array('index' => 1, 'name' => 'foo'),
			array('index' => 2, 'name' => 'bar'),
			array('index' => 3, 'name' => 'baz'),
		)));

		$this->assertEquals('foobarbaz', $m->render('{{%IMPLICIT-ITERATOR}}{{#items}}{{#.}}{{name}}{{/.}}{{/items}}'));
		$this->assertEquals('123', $m->render('{{%IMPLICIT-ITERATOR iterator=i}}{{%DOT-NOTATION}}{{#items}}{{i.index}}{{/items}}'));
		$this->assertEquals('foobarbaz', $m->render('{{%IMPLICIT-ITERATOR iterator=i}}{{%DOT-NOTATION}}{{#items}}{{i.name}}{{/items}}'));
	}

}