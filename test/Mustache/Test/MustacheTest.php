<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test;

use Mustache\Compiler;
use Mustache\Mustache;
use Mustache\Loader\StringLoader;
use Mustache\Loader\ArrayLoader;
use Mustache\Parser;
use Mustache\Tokenizer;

/**
 * @group unit
 */
class MustacheTest extends \PHPUnit_Framework_TestCase {

	private static $tempDir;

	public static function setUpBeforeClass() {
		self::$tempDir = sys_get_temp_dir() . '/mustache_test';
		if (file_exists(self::$tempDir)) {
			self::rmdir(self::$tempDir);
		}
	}

	public function testConstructor() {
		$loader         = new StringLoader;
		$partialsLoader = new ArrayLoader;
		$mustache       = new Mustache(array(
			'template_class_prefix' => '__whot__',
			'cache' => self::$tempDir,
			'loader' => $loader,
			'partials_loader' => $partialsLoader,
			'partials' => array(
				'foo' => '{{ foo }}',
			),
			'helpers' => array(
				'foo' => function() { return 'foo'; },
				'bar' => 'BAR',
			),
			'charset' => 'ISO-8859-1',
		));

		$this->assertSame($loader, $mustache->getLoader());
		$this->assertSame($partialsLoader, $mustache->getPartialsLoader());
		$this->assertEquals('{{ foo }}', $partialsLoader->load('foo'));
		$this->assertContains('__whot__', $mustache->getTemplateClassName('{{ foo }}'));
		$this->assertEquals('ISO-8859-1', $mustache->getCharset());
		$this->assertTrue($mustache->hasHelper('foo'));
		$this->assertTrue($mustache->hasHelper('bar'));
		$this->assertFalse($mustache->hasHelper('baz'));
	}

	public function testRender() {
		$source = '{{ foo }}';
		$data   = array('bar' => 'baz');
		$output = 'TEH OUTPUT';

		$template = $this->getMockBuilder('Mustache\Template')
			->disableOriginalConstructor()
			->getMock();

		$mustache = new MustacheStub;
		$mustache->template = $template;

		$template->expects($this->once())
			->method('render')
			->with($data)
			->will($this->returnValue($output));

		$this->assertEquals($output, $mustache->render($source, $data));
		$this->assertEquals($source, $mustache->source);
	}

	public function testSettingServices() {
		$loader    = new StringLoader;
		$tokenizer = new Tokenizer;
		$parser    = new Parser;
		$compiler  = new Compiler;
		$mustache  = new Mustache;

		$this->assertNotSame($loader, $mustache->getLoader());
		$mustache->setLoader($loader);
		$this->assertSame($loader, $mustache->getLoader());

		$this->assertNotSame($loader, $mustache->getPartialsLoader());
		$mustache->setPartialsLoader($loader);
		$this->assertSame($loader, $mustache->getPartialsLoader());

		$this->assertNotSame($tokenizer, $mustache->getTokenizer());
		$mustache->setTokenizer($tokenizer);
		$this->assertSame($tokenizer, $mustache->getTokenizer());

		$this->assertNotSame($parser, $mustache->getParser());
		$mustache->setParser($parser);
		$this->assertSame($parser, $mustache->getParser());

		$this->assertNotSame($compiler, $mustache->getCompiler());
		$mustache->setCompiler($compiler);
		$this->assertSame($compiler, $mustache->getCompiler());
	}

	/**
	 * @group functional
	 */
	public function testCache() {
		$mustache = new Mustache(array(
			'template_class_prefix' => '__whot__',
			'cache' => self::$tempDir,
		));

		$source    = '{{ foo }}';
		$template  = $mustache->loadTemplate($source);
		$className = $mustache->getTemplateClassName($source);
		$fileName  = self::$tempDir . '/' . $className . '.php';
		$this->assertInstanceOf($className, $template);
		$this->assertFileExists($fileName);
		$this->assertContains("\nclass $className extends \Mustache\Template", file_get_contents($fileName));
	}

	/**
	 * @group functional
	 * @expectedException \RuntimeException
	 */
	public function testCacheFailsThrowException() {
		global $mustacheFilesystemRenameHax;

		$mustacheFilesystemRenameHax = true;

		$mustache = new Mustache(array('cache' => self::$tempDir));
		$mustache->loadTemplate('{{ foo }}');
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testImmutablePartialsLoadersThrowException() {
		$mustache = new Mustache(array(
			'partials_loader' => new StringLoader,
		));

		$mustache->setPartials(array('foo' => '{{ foo }}'));
	}

	public function testHelpers() {
		$foo = function() { return 'foo'; };
		$bar = 'BAR';
		$mustache = new Mustache(array('helpers' => array(
			'foo' => $foo,
			'bar' => $bar,
		)));

		$helpers = $mustache->getHelpers();
		$this->assertTrue($mustache->hasHelper('foo'));
		$this->assertTrue($mustache->hasHelper('bar'));
		$this->assertTrue($helpers->has('foo'));
		$this->assertTrue($helpers->has('bar'));
		$this->assertSame($foo, $mustache->getHelper('foo'));
		$this->assertSame($bar, $mustache->getHelper('bar'));

		$mustache->removeHelper('bar');
		$this->assertFalse($mustache->hasHelper('bar'));
		$mustache->addHelper('bar', $bar);
		$this->assertSame($bar, $mustache->getHelper('bar'));

		$baz = function($text) { return '__'.$text.'__'; };
		$this->assertFalse($mustache->hasHelper('baz'));
		$this->assertFalse($helpers->has('baz'));

		$mustache->addHelper('baz', $baz);
		$this->assertTrue($mustache->hasHelper('baz'));
		$this->assertTrue($helpers->has('baz'));

		// ... and a functional test
		$tpl = $mustache->loadTemplate('{{foo}} - {{bar}} - {{#baz}}qux{{/baz}}');
		$this->assertEquals('foo - BAR - __qux__', $tpl->render());
		$this->assertEquals('foo - BAR - __qux__', $tpl->render(array('qux' => "won't mess things up")));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetHelpersThrowsExceptions() {
		$mustache = new Mustache;
		$mustache->setHelpers('monkeymonkeymonkey');
	}

	private static function rmdir($path) {
		$path = rtrim($path, '/').'/';
		$handle = opendir($path);
		while (($file = readdir($handle)) !== false) {
			if ($file == '.' || $file == '..') {
				continue;
			}

			$fullpath = $path.$file;
			if (is_dir($fullpath)) {
				self::rmdir($fullpath);
			} else {
				unlink($fullpath);
			}
		}

		closedir($handle);
		rmdir($path);
	}
}

class MustacheStub extends Mustache {
	public $source;
	public $template;
	public function loadTemplate($source) {
		$this->source = $source;

		return $this->template;
	}
}


// It's prob'ly best if you ignore this bit.

namespace Mustache;

function rename($a, $b) {
	global $mustacheFilesystemRenameHax;

	return ($mustacheFilesystemRenameHax) ? false : \rename($a, $b);
}
