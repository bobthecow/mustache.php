<?php

namespace Mustache\Test\Functional;

use Mustache\Mustache;

/**
 * @group mustache_injection
 * @group functional
 */
class MustacheInjectionTest extends \PHPUnit_Framework_TestCase {

	private $mustache;

	public function setUp() {
		$this->mustache = new Mustache;
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
