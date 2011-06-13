<?php

require_once '../Mustache.php';

/**
 * @group pragmas
 */
class MustachePragmaTest extends PHPUnit_Framework_TestCase {

	public function testUnknownPragmaException() {
		$m = new Mustache();

		try {
			$m->render('{{%I-HAVE-THE-GREATEST-MUSTACHE}}');
		} catch (MustacheException $e) {
			$this->assertEquals(MustacheException::UNKNOWN_PRAGMA, $e->getCode(), 'Caught exception code was not MustacheException::UNKNOWN_PRAGMA');
			return;
		}

		$this->fail('Mustache should have thrown an unknown pragma exception');
	}

	public function testPragmaReplace() {
		$m = new Mustache();
		$this->assertEquals('', $m->render('{{%UNESCAPED}}'), 'Pragma tag not removed');
	}

	public function testPragmaReplaceMultiple() {
		$m = new Mustache();

		$this->assertEquals('', $m->render('{{%  UNESCAPED  }}'), 'Pragmas should allow whitespace');
		$this->assertEquals('', $m->render('{{% 	UNESCAPED 	foo=bar  }}'), 'Pragmas should allow whitespace');
		$this->assertEquals('', $m->render("{{%UNESCAPED}}\n{{%UNESCAPED}}"), 'Multiple pragma tags not removed');
		$this->assertEquals(' ', $m->render('{{%UNESCAPED}} {{%UNESCAPED}}'), 'Multiple pragma tags not removed');
	}

	public function testPragmaReplaceNewline() {
		$m = new Mustache();
		$this->assertEquals('', $m->render("{{%UNESCAPED}}\n"), 'Trailing newline after pragma tag not removed');
		$this->assertEquals("\n", $m->render("\n{{%UNESCAPED}}\n"), 'Too many newlines removed with pragma tag');
		$this->assertEquals("1\n23", $m->render("1\n2{{%UNESCAPED}}\n3"), 'Wrong newline removed with pragma tag');
	}

	public function testPragmaReset() {
		$m = new Mustache('', array('symbol' => '>>>'));
		$this->assertEquals('>>>', $m->render('{{{symbol}}}'));
		$this->assertEquals('>>>', $m->render('{{%UNESCAPED}}{{symbol}}'));
		$this->assertEquals('>>>', $m->render('{{{symbol}}}'));
	}
}