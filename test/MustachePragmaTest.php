<?php

require_once '../Mustache.php';
require_once 'PHPUnit/Framework.php';

class MustachePragmaTest extends PHPUnit_Framework_TestCase {

	public function testUnknownPragmaException() {
		$m = new Mustache();
		$caught_exception = null;

		try {
			$m->render('{{%I-HAVE-THE-GREATEST-MUSTACHE}}');
		} catch (Exception $e) {
			$caught_exception = $e;
		}

		$this->assertNotNull($caught_exception, 'No exception caught');
		$this->assertType('MustacheException', $caught_exception);
		$this->assertEquals($caught_exception->getCode(), MustacheException::UNKNOWN_PRAGMA, 'Caught exception code was not MustacheException::UNKNOWN_PRAGMA');
	}

	public function testPragmaReplace() {
		$m = new Mustache();
		$this->assertEquals($m->render('{{%DOT-NOTATION}}'), '', 'Pragma tag not removed');
	}

	public function testPragmaReplaceMultiple() {
		$m = new Mustache();
		$this->assertEquals($m->render("{{%DOT-NOTATION}}\n{{%DOT-NOTATION}}"), '', 'Multiple pragma tags not removed');
		$this->assertEquals($m->render('{{%DOT-NOTATION}} {{%DOT-NOTATION}}'), ' ', 'Multiple pragma tags not removed');
	}

	public function testPragmaReplaceNewline() {
		$m = new Mustache();
		$this->assertEquals($m->render("{{%DOT-NOTATION}}\n"), '', 'Trailing newline after pragma tag not removed');
		$this->assertEquals($m->render("\n{{%DOT-NOTATION}}\n"), "\n", 'Too many newlines removed with pragma tag');
		$this->assertEquals($m->render("1\n2{{%DOT-NOTATION}}\n3"), "1\n23", 'Wrong newline removed with pragma tag');
	}
}