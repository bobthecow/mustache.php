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
	 * @group interpolation
	 * @expectedException MustacheException
	 */
	public function testThrowsUnknownVariableException() {
		$this->pickyMustache->render('{{not_a_variable}}');
	}

	/**
	 * @group sections
	 * @expectedException MustacheException
	 */
	public function testThrowsUnclosedSectionException() {
		$this->pickyMustache->render('{{#unclosed}}');
	}

	/**
	 * @group sections
	 * @expectedException MustacheException
	 */
	public function testThrowsUnclosedInvertedSectionException() {
		$this->pickyMustache->render('{{^unclosed}}');
	}

	/**
	 * @group sections
	 * @expectedException MustacheException
	 */
	public function testThrowsUnexpectedCloseSectionException() {
		$this->pickyMustache->render('{{/unopened}}');
	}

	/**
	 * @group partials
	 * @expectedException MustacheException
	 */
	public function testThrowsUnknownPartialException() {
		$this->pickyMustache->render('{{>impartial}}');
	}

	/**
	 * @group pragmas
	 * @expectedException MustacheException
	 */
	public function testThrowsUnknownPragmaException() {
		$this->pickyMustache->render('{{%SWEET-MUSTACHE-BRO}}');
	}

	/**
	 * @group sections
	 */
	public function testDoesntThrowUnclosedSectionException() {
		$this->assertEquals('', $this->slackerMustache->render('{{#unclosed}}'));
	}

	/**
	 * @group sections
	 */
	public function testDoesntThrowUnexpectedCloseSectionException() {
		$this->assertEquals('', $this->slackerMustache->render('{{/unopened}}'));
	}

	/**
	 * @group partials
	 */
	public function testDoesntThrowUnknownPartialException() {
		$this->assertEquals('', $this->slackerMustache->render('{{>impartial}}'));
	}

	/**
	 * @group pragmas
	 * @expectedException MustacheException
	 */
	public function testGetPragmaOptionsThrowsExceptionsIfItThinksYouHaveAPragmaButItTurnsOutYouDont() {
		$mustache = new TestableMustache();
		$mustache->testableGetPragmaOptions('PRAGMATIC');
	}

	public function testOverrideThrownExceptionsViaConstructorOptions() {
		$exceptions = array(
			MustacheException::UNKNOWN_VARIABLE,
			MustacheException::UNCLOSED_SECTION,
			MustacheException::UNEXPECTED_CLOSE_SECTION,
			MustacheException::UNKNOWN_PARTIAL,
			MustacheException::UNKNOWN_PRAGMA,
		);

		$one = new TestableMustache(null, null, null, array(
			'throws_exceptions' => array_fill_keys($exceptions, true)
		));

		$thrownExceptions = $one->getThrownExceptions();
		foreach ($exceptions as $exception) {
			$this->assertTrue($thrownExceptions[$exception]);
		}

		$two = new TestableMustache(null, null, null, array(
			'throws_exceptions' => array_fill_keys($exceptions, false)
		));

		$thrownExceptions = $two->getThrownExceptions();
		foreach ($exceptions as $exception) {
			$this->assertFalse($thrownExceptions[$exception]);
		}
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

	public function getThrownExceptions() {
		return $this->_throwsExceptions;
	}
}
