<?php

require_once '../Mustache.php';

class MustacheExceptionTest extends PHPUnit_Framework_TestCase {

	const TEST_CLASS = 'Mustache';

	protected $pickyMustache;
	protected $slackerMustache;
	
	public function setUp() {
		$this->pickyMustache      = new PickyMustache();
		$this->slackerMustache    = new SlackerMustache();
	}

	/**
	 * @expectedException MustacheException
	 */
	public function testThrowsUnknownVariableException() {
		$this->pickyMustache->render('{{not_a_variable}}');
	}

	/**
	 * @expectedException MustacheException
	 */
	public function testThrowsUnclosedSectionException() {
		$this->pickyMustache->render('{{#unclosed}}');
	}

	/**
	 * @expectedException MustacheException
	 */
	public function testThrowsUnexpectedCloseSectionException() {
		$this->pickyMustache->render('{{/unopened}}');
	}

	/**
	 * @expectedException MustacheException
	 */
	public function testThrowsUnknownPartialException() {
		$this->pickyMustache->render('{{>impartial}}');
	}

	/**
	 * @expectedException MustacheException
	 */
	public function testThrowsUnknownPragmaException() {
		$this->pickyMustache->render('{{%SWEET-MUSTACHE-BRO}}');
	}

	public function testDoesntThrowUnclosedSectionException() {
		$this->assertEquals('', $this->slackerMustache->render('{{#unclosed}}'));
	}

	public function testDoesntThrowUnexpectedCloseSectionException() {
		$this->assertEquals('', $this->slackerMustache->render('{{/unopened}}'));
	}

	public function testDoesntThrowUnknownPartialException() {
		$this->assertEquals('', $this->slackerMustache->render('{{>impartial}}'));
	}

	/**
	 * @expectedException MustacheException
	 */
	public function testGetPragmaOptionsThrowsExceptionsIfItThinksYouHaveAPragmaButItTurnsOutYouDont() {
		$mustache = new TestableMustache();
		$mustache->testableGetPragmaOptions('PRAGMATIC');
	}
}

class PickyMustache extends Mustache {
	protected $_throwsExceptions = array(
		MustacheException::UNKNOWN_VARIABLE         => true,
		MustacheException::UNCLOSED_SECTION         => true,
		MustacheException::UNEXPECTED_CLOSE_SECTION => true,
		MustacheException::UNKNOWN_PARTIAL          => true,
		MustacheException::UNKNOWN_PRAGMA           => true,
	);
}

class SlackerMustache extends Mustache {
	protected $_throwsExceptions = array(
		MustacheException::UNKNOWN_VARIABLE         => false,
		MustacheException::UNCLOSED_SECTION         => false,
		MustacheException::UNEXPECTED_CLOSE_SECTION => false,
		MustacheException::UNKNOWN_PARTIAL          => false,
		MustacheException::UNKNOWN_PRAGMA           => false,
	);
}

class TestableMustache extends Mustache {
	public function testableGetPragmaOptions($pragma_name) {
		return $this->_getPragmaOptions($pragma_name);
	}
}