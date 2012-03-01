<?php

namespace Mustache\Test;

use Mustache\Context;
use Mustache\Mustache;
use Mustache\Template;

/**
 * @group unit
 */
class TemplateTest extends \PHPUnit_Framework_TestCase {
	public function testConstructor() {
		$mustache = new Mustache;
		$template = new TemplateStub($mustache);
		$this->assertSame($mustache, $template->getMustache());
	}

	public function testRendering() {
		$rendered = '<< wheee >>';
		$mustache = new Mustache;
		$template = new TemplateStub($mustache);
		$template->rendered = $rendered;
		$context  = new Context;

		$this->assertEquals($rendered, $template());
		$this->assertEquals($rendered, $template->render());
		$this->assertEquals($rendered, $template->renderInternal($context));
		$this->assertEquals($rendered, $template->render(array('foo' => 'bar')));
	}
}

class TemplateStub extends Template {
	public $rendered;

	public function getMustache() {
		return $this->mustache;
	}

	public function renderInternal(Context $context) {
		return $this->rendered;
	}
}
