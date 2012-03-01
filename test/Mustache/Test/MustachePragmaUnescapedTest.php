<?php

require_once '../Mustache.php';

/**
 * @group pragmas
 */
class MustachePragmaUnescapedTest extends PHPUnit_Framework_TestCase {

	public function testPragmaUnescaped() {
		$m = new Mustache(null, array('title' => 'Bear > Shark'));
		
		$this->assertEquals('Bear > Shark', $m->render('{{%UNESCAPED}}{{title}}'));
		$this->assertEquals('Bear &gt; Shark', $m->render('{{title}}'));
		$this->assertEquals('Bear &gt; Shark', $m->render('{{%UNESCAPED}}{{{title}}}'));
		$this->assertEquals('Bear > Shark', $m->render('{{{title}}}'));
	}

}