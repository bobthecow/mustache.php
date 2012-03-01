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
 * @group unit
 */
class Mustache_Test_MustacheTest extends PHPUnit_Framework_TestCase {

	private static $tempDir;

	public static function setUpBeforeClass() {
		self::$tempDir = sys_get_temp_dir() . '/mustache_test';
		if (file_exists(self::$tempDir)) {
			self::rmdir(self::$tempDir);
		}
	}

	public function testConstructor() {
		$loader         = new Mustache_Loader_StringLoader;
		$partialsLoader = new Mustache_Loader_ArrayLoader;
		$mustache       = new Mustache_Mustache(array(
			'template_class_prefix' => '__whot__',
			'cache' => self::$tempDir,
			'loader' => $loader,
			'partials_loader' => $partialsLoader,
			'partials' => array(
				'foo' => '{{ foo }}',
			),
			'charset' => 'ISO-8859-1',
		));

		$this->assertSame($loader, $mustache->getLoader());
		$this->assertSame($partialsLoader, $mustache->getPartialsLoader());
		$this->assertEquals('{{ foo }}', $partialsLoader->load('foo'));
		$this->assertContains('__whot__', $mustache->getTemplateClassName('{{ foo }}'));
		$this->assertEquals('ISO-8859-1', $mustache->getCharset());
	}

	public function testSettingServices() {
		$loader    = new Mustache_Loader_StringLoader;
		$tokenizer = new Mustache_Tokenizer;
		$parser    = new Mustache_Parser;
		$compiler  = new Mustache_Compiler;
		$mustache  = new Mustache_Mustache;

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
		$mustache = new Mustache_Mustache(array(
			'template_class_prefix' => '__whot__',
			'cache' => self::$tempDir,
		));

		$source    = '{{ foo }}';
		$template  = $mustache->loadTemplate($source);
		$className = $mustache->getTemplateClassName($source);
		$fileName  = self::$tempDir . '/' . $className . '.php';
		$this->assertInstanceOf($className, $template);
		$this->assertFileExists($fileName);
		$this->assertContains("\nclass $className extends Mustache_Template", file_get_contents($fileName));
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testImmutablePartialsLoadersThrowException() {
		$mustache = new Mustache_Mustache(array(
			'partials_loader' => new Mustache_Loader_StringLoader,
		));

		$mustache->setPartials(array('foo' => '{{ foo }}'));
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
