<?php

namespace Mustache\Test\Functional;

use Mustache\Mustache;

/**
 * @group lambdas
 * @group functional
 */
class HigherOrderSectionsTest extends \PHPUnit_Framework_TestCase {

	private $mustache;

	public function setUp() {
		$this->mustache = new Mustache;
	}

	public function testAnonymousFunctionSectionCallback() {
		$tpl = $this->mustache->loadTemplate('{{#wrapper}}{{name}}{{/wrapper}}');

		$foo = new Foo;
		$foo->name = 'Mario';
		$foo->wrapper = function($text) {
			return sprintf('<div class="anonymous">%s</div>', $text);
		};

		$this->assertEquals(sprintf('<div class="anonymous">%s</div>', $foo->name), $tpl->render($foo));
	}

	public function testSectionCallback() {
		$one = $this->mustache->loadTemplate('{{name}}');
		$two = $this->mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');

		$foo = new Foo;
		$foo->name = 'Luigi';

		$this->assertEquals($foo->name, $one->render($foo));
		$this->assertEquals(sprintf('<em>%s</em>', $foo->name), $two->render($foo));
	}

	public function testRuntimeSectionCallback() {
		$tpl = $this->mustache->loadTemplate('{{#double_wrap}}{{name}}{{/double_wrap}}');

		$foo = new Foo;
		$foo->double_wrap = array($foo, 'wrapWithBoth');

		$this->assertEquals(sprintf('<strong><em>%s</em></strong>', $foo->name), $tpl->render($foo));
	}

	public function testStaticSectionCallback() {
		$tpl = $this->mustache->loadTemplate('{{#trimmer}}    {{name}}    {{/trimmer}}');

		$foo = new Foo;
		$foo->trimmer = array(get_class($foo), 'staticTrim');

		$this->assertEquals($foo->name, $tpl->render($foo));
	}

	public function testViewArraySectionCallback() {
		$tpl = $this->mustache->loadTemplate('{{#trim}}    {{name}}    {{/trim}}');

		$foo = new Foo;

		$data = array(
			'name' => 'Bob',
			'trim' => array(get_class($foo), 'staticTrim'),
		);

		$this->assertEquals($data['name'], $tpl->render($data));
	}

	public function testViewArrayAnonymousSectionCallback() {
		$tpl = $this->mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');

		$data = array(
			'name' => 'Bob',
			'wrap' => function($text) {
				return sprintf('[[%s]]', $text);
			}
		);

		$this->assertEquals(
			sprintf('[[%s]]', $data['name']),
			$tpl->render($data)
		);
	}

	public function testMonsters() {
		$tpl = $this->mustache->loadTemplate('{{#title}}{{title}} {{/title}}{{name}}');

		$frank = new Monster();
		$frank->title = 'Dr.';
		$frank->name  = 'Frankenstein';
		$this->assertEquals('Dr. Frankenstein', $tpl->render($frank));

		$dracula = new Monster();
		$dracula->title = 'Count';
		$dracula->name  = 'Dracula';
		$this->assertEquals('Count Dracula', $tpl->render($dracula));
	}
}

class Foo {
	public $name = 'Justin';
	public $lorem = 'Lorem ipsum dolor sit amet,';
	public $wrap;

	public function __construct() {
		$this->wrap = function($text) {
			return sprintf('<em>%s</em>', $text);
		};
	}

	public function wrapWithEm($text) {
		return sprintf('<em>%s</em>', $text);
	}

	public function wrapWithStrong($text) {
		return sprintf('<strong>%s</strong>', $text);
	}

	public function wrapWithBoth($text) {
		return self::wrapWithStrong(self::wrapWithEm($text));
	}

	public static function staticTrim($text) {
		return trim($text);
	}
}

class Monster {
	public $title;
	public $name;
}
