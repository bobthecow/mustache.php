<?php

require_once '../Mustache.php';
require_once './lib/yaml/lib/sfYamlParser.php';

/**
 * A PHPUnit test case wrapping the Mustache Spec
 *
 * @group mustache-spec
 */
class MustacheSpecTest extends PHPUnit_Framework_TestCase {

	/**
	 * For some reason data providers can't mark tests skipped, so this test exists
	 * simply to provide a 'skipped' test if the `spec` submodule isn't initialized.
	 */
	public function testSpecInitialized() {
		$spec_dir = dirname(__FILE__) . '/spec/specs/';
		if (!file_exists($spec_dir)) {
			$this->markTestSkipped('Mustache spec submodule not initialized: run "git submodule update --init"');
		}
	}

	/**
	 * @group comments
	 * @dataProvider loadCommentSpec
	 */
	public function testCommentSpec($template, $data, $partials, $expected, $desc) {
		$m = new Mustache($template, $data, $partials);
		$this->assertEquals($expected, $m->render(), $desc);
	}

	/**
	 * @group delimiters
	 * @dataProvider loadDelimitersSpec
	 */
	public function testDelimitersSpec($template, $data, $partials, $expected, $desc) {
		$m = new Mustache($template, $data, $partials);
		$this->assertEquals($expected, $m->render(), $desc);
	}

	/**
	 * @group interpolation
	 * @dataProvider loadInterpolationSpec
	 */
	public function testInterpolationSpec($template, $data, $partials, $expected, $desc) {
		$m = new Mustache($template, $data, $partials);
		$this->assertEquals($expected, $m->render(), $desc);
	}

	/**
	 * @group inverted-sections
	 * @dataProvider loadInvertedSpec
	 */
	public function testInvertedSpec($template, $data, $partials, $expected, $desc) {
		$m = new Mustache($template, $data, $partials);
		$this->assertEquals($expected, $m->render(), $desc);
	}

	// /**
	//  * @group lambdas
	//  * @dataProvider loadLambdasSpec
	//  */
	// public function testLambdasSpec($template, $data, $partials, $expected, $desc) {
	// 	$this->markTestSkipped("Lambdas for PHP haven't made it into the spec yet, so we'll skip them to avoid a bajillion failed tests.");
	//
	// 	if (!version_compare(PHP_VERSION, '5.3.0', '>=')) {
	// 		$this->markTestSkipped('Unable to test Lambdas spec with PHP < 5.3.');
	// 	}
	//
	// 	$m = new Mustache($template, $data, $partials);
	// 	$this->assertEquals($expected, $m->render(), $desc);
	// }

	/**
	 * @group partials
	 * @dataProvider loadPartialsSpec
	 */
	public function testPartialsSpec($template, $data, $partials, $expected, $desc) {
		$m = new Mustache($template, $data, $partials);
		$this->assertEquals($expected, $m->render(), $desc);
	}

	/**
	 * @group sections
	 * @dataProvider loadSectionsSpec
	 */
	public function testSectionsSpec($template, $data, $partials, $expected, $desc) {
		$m = new Mustache($template, $data, $partials);
		$this->assertEquals($expected, $m->render(), $desc);
	}

	public function loadCommentSpec() {
		return $this->loadSpec('comments');
	}

	public function loadDelimitersSpec() {
		return $this->loadSpec('delimiters');
	}

	public function loadInterpolationSpec() {
		return $this->loadSpec('interpolation');
	}

	public function loadInvertedSpec() {
		return $this->loadSpec('inverted');
	}

	// public function loadLambdasSpec() {
	// 	return $this->loadSpec('lambdas');
	// }

	public function loadPartialsSpec() {
		return $this->loadSpec('partials');
	}

	public function loadSectionsSpec() {
		return $this->loadSpec('sections');
	}

	/**
	 * Data provider for the mustache spec test.
	 *
	 * Loads YAML files from the spec and converts them to PHPisms.
	 *
	 * @access public
	 * @return array
	 */
	protected function loadSpec($name) {
		$filename = dirname(__FILE__) . '/spec/specs/' . $name . '.yml';
		if (!file_exists($filename)) {
			return array();
		}

		$data = array();

		$yaml = new sfYamlParser();

		$spec = $yaml->parse(file_get_contents($filename));
		foreach ($spec['tests'] as $test) {
			$data[] = array($test['template'], $test['data'], isset($test['partials']) ? $test['partials'] : array(), $test['expected'], $test['desc']);
		}
		return $data;
	}
}