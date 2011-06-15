<?php

require_once '../Mustache.php';

/**
 * @group pragmas
 */
class MustacheSpecialSectionTest extends PHPUnit_Framework_TestCase {

	// Comments

	public function testCommentSingleLine() {
		$data = array();
		$template = '{{#!}}Over {{/!}}Here';
		$output = 'Here';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}

	public function testCommentIgnoreTags() {
		$data = array('bro' => 'dawg');
		$template = '{{#!}}Don\'t render me, {{bro}}.{{/!}}';
		$output = '';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}

	public function testCommentMultiLine() {
		$data = array();
		$template = '|
        | This Is
          {{#!}}
        Not
          {{/!}}
        | A Line';
		$output = '|
        | This Is
        | A Line';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}

	// Literals
	
	public function testLiteralSingleLine() {
		$data = array();
		$template = '{{#`}}Over {{/`}}Here';
		$output = 'Over Here';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}
	
	public function testLiteralIgnoreTags() {
		$data = array('bro' => 'dawg');
		$template = '{{#`}}Don\'t render me, {{bro}}.{{/`}}';
		$output = 'Don\'t render me, {{bro}}.';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}
	
	public function testLiteralMultiLine() {
		$data = array('foo' => array('bar' => 'baz'));
		$template = '|
        | var template = "
          {{#`}}
        | {{#foo}}
        |   {{bar}}
        | {{/foo}}
          {{/`}}
        | ";';
		$output = '|
        | var template = "
        | {{#foo}}
        |   {{bar}}
        | {{/foo}}
        | ";';
		$m = new Mustache();
		$this->assertEquals($output, $m->render($template, $data));
	}

}