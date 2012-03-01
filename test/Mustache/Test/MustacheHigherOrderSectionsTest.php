<?php

require_once '../Mustache.php';

class MustacheHigherOrderSectionsTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		$this->foo = new Foo();
	}

	public function testAnonymousFunctionSectionCallback() {
		if (version_compare(PHP_VERSION, '5.3.0', '<')) {
			$this->markTestSkipped('Unable to test anonymous function section callbacks in PHP < 5.3');
			return;
		}

		$this->foo->wrapper = function($text) {
			return sprintf('<div class="anonymous">%s</div>', $text);
		};

		$this->assertEquals(
			sprintf('<div class="anonymous">%s</div>', $this->foo->name),
			$this->foo->render('{{#wrapper}}{{name}}{{/wrapper}}')
		);
	}

	public function testSectionCallback() {
		$this->assertEquals(sprintf('%s', $this->foo->name), $this->foo->render('{{name}}'));
		$this->assertEquals(sprintf('<em>%s</em>', $this->foo->name), $this->foo->render('{{#wrap}}{{name}}{{/wrap}}'));
	}

	public function testRuntimeSectionCallback() {
		$this->foo->double_wrap = array($this->foo, 'wrapWithBoth');
		$this->assertEquals(
			sprintf('<strong><em>%s</em></strong>', $this->foo->name),
			$this->foo->render('{{#double_wrap}}{{name}}{{/double_wrap}}')
		);
	}

	public function testStaticSectionCallback() {
		$this->foo->trimmer = array(get_class($this->foo), 'staticTrim');
		$this->assertEquals($this->foo->name, $this->foo->render('{{#trimmer}}    {{name}}    {{/trimmer}}'));
	}

	public function testViewArraySectionCallback() {
		$data = array(
			'name' => 'Bob',
			'trim' => array(get_class($this->foo), 'staticTrim'),
		);
		$this->assertEquals($data['name'], $this->foo->render('{{#trim}}    {{name}}    {{/trim}}', $data));
	}

	public function testViewArrayAnonymousSectionCallback() {
		if (version_compare(PHP_VERSION, '5.3.0', '<')) {
			$this->markTestSkipped('Unable to test anonymous function section callbacks in PHP < 5.3');
			return;
		}
		$data = array(
			'name' => 'Bob',
			'wrap' => function($text) {
				return sprintf('[[%s]]', $text);
			}
		);
		$this->assertEquals(
			sprintf('[[%s]]', $data['name']),
			$this->foo->render('{{#wrap}}{{name}}{{/wrap}}', $data)
		);
	}

	public function testMonsters() {
		$frank = new Monster();
		$frank->title = 'Dr.';
		$frank->name  = 'Frankenstein';
		$this->assertEquals('Dr. Frankenstein', $frank->render());

		$dracula = new Monster();
		$dracula->title = 'Count';
		$dracula->name  = 'Dracula';
		$this->assertEquals('Count Dracula', $dracula->render());
	}
}

class Foo extends Mustache {
	public $name = 'Justin';
	public $lorem = 'Lorem ipsum dolor sit amet,';
	public $wrap;

	public function __construct($template = null, $view = null, $partials = null) {
		$this->wrap = array($this, 'wrapWithEm');
		parent::__construct($template, $view, $partials);
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

class Monster extends Mustache {
	public $_template = '{{#title}}{{title}} {{/title}}{{name}}';
	public $title;
	public $name;
}