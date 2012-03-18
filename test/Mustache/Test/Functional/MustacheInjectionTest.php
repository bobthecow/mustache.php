<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group mustache_injection
 * @group functional
 */
class Mustache_Test_Functional_MustacheInjectionTest extends PHPUnit_Framework_TestCase {

	private $mustache;

	public function setUp() {
		$this->mustache = new Mustache_Mustache;
	}

	// interpolation

	public function testInterpolationInjection() {
		$tpl = $this->mustache->loadTemplate('{{ a }}');

		$data = array(
			'a' => '{{ b }}',
			'b' => 'FAIL'
		);

		$this->assertEquals('{{ b }}', $tpl->render($data));
	}

	public function testUnescapedInterpolationInjection() {
		$tpl = $this->mustache->loadTemplate('{{{ a }}}');

		$data = array(
			'a' => '{{ b }}',
			'b' => 'FAIL'
		);

		$this->assertEquals('{{ b }}', $tpl->render($data));
	}


	// sections

	public function testSectionInjection() {
		$tpl = $this->mustache->loadTemplate('{{# a }}{{ b }}{{/ a }}');

		$data = array(
			'a' => true,
			'b' => '{{ c }}',
			'c' => 'FAIL'
		);

		$this->assertEquals('{{ c }}', $tpl->render($data));
	}

	public function testUnescapedSectionInjection() {
		$tpl = $this->mustache->loadTemplate('{{# a }}{{{ b }}}{{/ a }}');

		$data = array(
			'a' => true,
			'b' => '{{ c }}',
			'c' => 'FAIL'
		);

		$this->assertEquals('{{ c }}', $tpl->render($data));
	}


	// partials

	public function testPartialInjection() {
		$tpl = $this->mustache->loadTemplate('{{> partial }}');
		$this->mustache->setPartials(array(
			'partial' => '{{ a }}',
		));

		$data = array(
			'a' => '{{ b }}',
			'b' => 'FAIL'
		);

		$this->assertEquals('{{ b }}', $tpl->render($data));
	}

	public function testPartialUnescapedInjection() {
		$tpl = $this->mustache->loadTemplate('{{> partial }}');
		$this->mustache->setPartials(array(
			'partial' => '{{{ a }}}',
		));

		$data = array(
			'a' => '{{ b }}',
			'b' => 'FAIL'
		);

		$this->assertEquals('{{ b }}', $tpl->render($data));
	}


	// lambdas

	public function testLambdaInterpolationInjection() {
		$tpl = $this->mustache->loadTemplate('{{ a }}');

		$data = array(
			'a' => function() {
				return '{{ b }}';
			},
			'b' => '{{ c }}',
			'c' => 'FAIL'
		);

		$this->assertEquals('{{ c }}', $tpl->render($data));
	}

	public function testLambdaSectionInjection() {
		$tpl = $this->mustache->loadTemplate('{{# a }}b{{/ a }}');

		$data = array(
			'a' => function ($text) {
				return '{{ ' . $text . ' }}';
			},
			'b' => '{{ c }}',
			'c' => 'FAIL'
		);

		$this->assertEquals('{{ c }}', $tpl->render($data));
	}
}
