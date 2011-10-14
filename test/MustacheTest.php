<?php

require_once '../Mustache.php';

/**
 * A PHPUnit test case for Mustache.php.
 *
 * This is a very basic, very rudimentary unit test case. It's probably more important to have tests
 * than to have elegant tests, so let's bear with it for a bit.
 *
 * This class assumes an example directory exists at `../examples` with the following structure:
 *
 * @code
 *    examples
 *        foo
 *            Foo.php
 *            foo.mustache
 *            foo.txt
 *        bar
 *            Bar.php
 *            bar.mustache
 *            bar.txt
 * @endcode
 *
 * To use this test:
 *
 *  1. {@link http://www.phpunit.de/manual/current/en/installation.html Install PHPUnit}
 *  2. run phpunit from the `test` directory:
 *        `phpunit MustacheTest`
 *  3. Fix bugs. Lather, rinse, repeat.
 *
 * @extends PHPUnit_Framework_TestCase
 */
class MustacheTest extends PHPUnit_Framework_TestCase {

	const TEST_CLASS = 'Mustache';

	protected $knownIssues = array(
		// Just the whitespace ones...
	);

	/**
	 * Test Mustache constructor.
	 *
	 * @access public
	 * @return void
	 */
	public function test__construct() {
		$template = '{{#mustaches}}{{#last}}and {{/last}}{{type}}{{^last}}, {{/last}}{{/mustaches}}';
		$data     = array(
			'mustaches' => array(
				array('type' => 'Natural'),
				array('type' => 'Hungarian'),
				array('type' => 'Dali'),
				array('type' => 'English'),
				array('type' => 'Imperial'),
				array('type' => 'Freestyle', 'last' => 'true'),
			)
		);
		$output = 'Natural, Hungarian, Dali, English, Imperial, and Freestyle';

		$m1 = new Mustache();
		$this->assertEquals($output, $m1->render($template, $data));

		$m2 = new Mustache($template);
		$this->assertEquals($output, $m2->render(null, $data));

		$m3 = new Mustache($template, $data);
		$this->assertEquals($output, $m3->render());

		$m4 = new Mustache(null, $data);
		$this->assertEquals($output, $m4->render($template));
	}

	/**
	 * @dataProvider constructorOptions
	 */
	public function testConstructorOptions($options, $charset, $delimiters, $pragmas) {
		$mustache = new MustacheExposedOptionsStub(null, null, null, $options);
		$this->assertEquals($charset,    $mustache->getCharset());
		$this->assertEquals($delimiters, $mustache->getDelimiters());
		$this->assertEquals($pragmas,    $mustache->getPragmas());
	}

	public function constructorOptions() {
		return array(
			array(
				array(),
				'UTF-8',
				array('{{', '}}'),
				array(),
			),
			array(
				array(
					'charset'    => 'UTF-8',
					'delimiters' => '<< >>',
					'pragmas'    => array(Mustache::PRAGMA_UNESCAPED => true)
				),
				'UTF-8',
				array('<<', '>>'),
				array(Mustache::PRAGMA_UNESCAPED => true),
			),
			array(
				array(
					'charset'    => 'cp866',
					'delimiters' => array('[[[[', ']]]]'),
					'pragmas'    => array(Mustache::PRAGMA_UNESCAPED => true)
				),
				'cp866',
				array('[[[[', ']]]]'),
				array(Mustache::PRAGMA_UNESCAPED => true),
			),
		);
	}

	/**
	 * @expectedException MustacheException
	 */
	public function testConstructorInvalidPragmaOptionsThrowExceptions() {
		$mustache = new Mustache(null, null, null, array('pragmas' => array('banana phone' => true)));
	}

	/**
	 * Test __toString() function.
	 *
	 * @access public
	 * @return void
	 */
	public function test__toString() {
		$m = new Mustache('{{first_name}} {{last_name}}', array('first_name' => 'Karl', 'last_name' => 'Marx'));

		$this->assertEquals('Karl Marx', $m->__toString());
		$this->assertEquals('Karl Marx', (string) $m);

		$m2 = $this->getMock(self::TEST_CLASS, array('render'), array());
		$m2->expects($this->once())
			->method('render')
			->will($this->returnValue('foo'));

		$this->assertEquals('foo', $m2->render());
	}

	public function test__toStringException() {
		$m = $this->getMock(self::TEST_CLASS, array('render'), array());
		$m->expects($this->once())
			->method('render')
			->will($this->throwException(new Exception));

		try {
			$out = (string) $m;
		} catch (Exception $e) {
			$this->fail('__toString should catch all exceptions');
		}
	}

	/**
	 * Test render().
	 *
	 * @access public
	 * @return void
	 */
	public function testRender() {
		$m = new Mustache();

		$this->assertEquals('', $m->render(''));
		$this->assertEquals('foo', $m->render('foo'));
		$this->assertEquals('', $m->render(null));

		$m2 = new Mustache('foo');
		$this->assertEquals('foo', $m2->render());

		$m3 = new Mustache('');
		$this->assertEquals('', $m3->render());

		$m3 = new Mustache();
		$this->assertEquals('', $m3->render(null));
	}

	/**
	 * Test render() with data.
	 *
	 * @group interpolation
	 */
	public function testRenderWithData() {
		$m = new Mustache('{{first_name}} {{last_name}}');
		$this->assertEquals('Charlie Chaplin', $m->render(null, array('first_name' => 'Charlie', 'last_name' => 'Chaplin')));
		$this->assertEquals('Zappa, Frank', $m->render('{{last_name}}, {{first_name}}', array('first_name' => 'Frank', 'last_name' => 'Zappa')));
	}

	/**
	 * @group partials
	 */
	public function testRenderWithPartials() {
		$m = new Mustache('{{>stache}}', null, array('stache' => '{{first_name}} {{last_name}}'));
		$this->assertEquals('Charlie Chaplin', $m->render(null, array('first_name' => 'Charlie', 'last_name' => 'Chaplin')));
		$this->assertEquals('Zappa, Frank', $m->render('{{last_name}}, {{first_name}}', array('first_name' => 'Frank', 'last_name' => 'Zappa')));
	}

	/**
	 * @group interpolation
	 * @dataProvider interpolationData
	 */
	public function testDoubleRenderMustacheTags($template, $context, $expected) {
		$m = new Mustache($template, $context);
		$this->assertEquals($expected, $m->render());
	}

	public function interpolationData() {
		return array(
			array(
				'{{#a}}{{=<% %>=}}{{b}} c<%={{ }}=%>{{/a}}',
				array('a' => array(array('b' => 'Do Not Render'))),
				'{{b}} c'
			),
			array(
				'{{#a}}{{b}}{{/a}}',
				array('a' => array('b' => '{{c}}'), 'c' => 'FAIL'),
				'{{c}}'
			),
		);
	}

	/**
	 * Mustache should allow newlines (and other whitespace) in comments and all other tags.
	 *
	 * @group comments
	 */
	public function testNewlinesInComments() {
		$m = new Mustache("{{! comment \n \t still a comment... }}");
		$this->assertEquals('', $m->render());
	}

	/**
	 * Mustache should return the same thing when invoked multiple times.
	 */
	public function testMultipleInvocations() {
		$m = new Mustache('x');
		$first = $m->render();
		$second = $m->render();

		$this->assertEquals('x', $first);
		$this->assertEquals($first, $second);
	}

	/**
	 * Mustache should return the same thing when invoked multiple times.
	 *
	 * @group interpolation
	 */
	public function testMultipleInvocationsWithTags() {
		$m = new Mustache('{{one}} {{two}}', array('one' => 'foo', 'two' => 'bar'));
		$first = $m->render();
		$second = $m->render();

		$this->assertEquals('foo bar', $first);
		$this->assertEquals($first, $second);
	}

	/**
	 * Mustache should not use templates passed to the render() method for subsequent invocations.
	 */
	public function testResetTemplateForMultipleInvocations() {
		$m = new Mustache('Sirve.');
		$this->assertEquals('No sirve.', $m->render('No sirve.'));
		$this->assertEquals('Sirve.', $m->render());

		$m2 = new Mustache();
		$this->assertEquals('No sirve.', $m2->render('No sirve.'));
		$this->assertEquals('', $m2->render());
	}

	/**
	 * Test the __clone() magic function.
	 *
	 * @group examples
	 * @dataProvider getExamples
	 *
	 * @param string $class
	 * @param string $template
	 * @param string $output
	 */
	public function test__clone($class, $template, $output) {
		if (isset($this->knownIssues[$class])) {
			return $this->markTestSkipped($this->knownIssues[$class]);
		}

		$m = new $class;
		$n = clone $m;

		$n_output = $n->render($template);

		$o = clone $n;

		$this->assertEquals($m->render($template), $n_output);
		$this->assertEquals($n_output, $o->render($template));

		$this->assertNotSame($m, $n);
		$this->assertNotSame($n, $o);
		$this->assertNotSame($m, $o);
	}

	/**
	 * Test everything in the `examples` directory.
	 *
	 * @group examples
	 * @dataProvider getExamples
	 *
	 * @param string $class
	 * @param string $template
	 * @param string $output
	 */
	public function testExamples($class, $template, $output) {
		if (isset($this->knownIssues[$class])) {
			return $this->markTestSkipped($this->knownIssues[$class]);
		}

		$m = new $class;
		$this->assertEquals($output, $m->render($template));
	}

	/**
	 * Data provider for testExamples method.
	 *
	 * Assumes that an `examples` directory exists inside parent directory.
	 * This examples directory should contain any number of subdirectories, each of which contains
	 * three files: one Mustache class (.php), one Mustache template (.mustache), and one output file
	 * (.txt).
	 *
	 * This whole mess will be refined later to be more intuitive and less prescriptive, but it'll
	 * do for now. Especially since it means we can have unit tests :)
	 *
	 * @return array
	 */
	public function getExamples() {
		$basedir = dirname(__FILE__) . '/../examples/';

		$ret = array();

		$files = new RecursiveDirectoryIterator($basedir);
		while ($files->valid()) {

			if ($files->hasChildren() && $children = $files->getChildren()) {
				$example  = $files->getSubPathname();
				$class    = null;
				$template = null;
				$output   = null;

				foreach ($children as $file) {
					if (!$file->isFile()) continue;

					$filename = $file->getPathname();
					$info = pathinfo($filename);

					if (isset($info['extension'])) {
						switch($info['extension']) {
							case 'php':
								$class = $info['filename'];
								include_once($filename);
								break;

							case 'mustache':
								$template = file_get_contents($filename);
								break;

							case 'txt':
								$output = file_get_contents($filename);
								break;
						}
					}
				}

				if (!empty($class)) {
					$ret[$example] = array($class, $template, $output);
				}
			}

			$files->next();
		}
		return $ret;
	}

	/**
	 * @group delimiters
	 */
	public function testCrazyDelimiters() {
		$m = new Mustache(null, array('result' => 'success'));
		$this->assertEquals('success', $m->render('{{=[[ ]]=}}[[ result ]]'));
		$this->assertEquals('success', $m->render('{{=(( ))=}}(( result ))'));
		$this->assertEquals('success', $m->render('{{={$ $}=}}{$ result $}'));
		$this->assertEquals('success', $m->render('{{=<.. ..>=}}<.. result ..>'));
		$this->assertEquals('success', $m->render('{{=^^ ^^}}^^ result ^^'));
		$this->assertEquals('success', $m->render('{{=// \\\\}}// result \\\\'));
	}

	/**
	 * @group delimiters
	 */
	public function testResetDelimiters() {
		$m = new Mustache(null, array('result' => 'success'));
		$this->assertEquals('success', $m->render('{{=[[ ]]=}}[[ result ]]'));
		$this->assertEquals('success', $m->render('{{=<< >>=}}<< result >>'));
		$this->assertEquals('success', $m->render('{{=<% %>=}}<% result %>'));
	}

	/**
	 * @group delimiters
	 */
	public function testStickyDelimiters() {
		$m = new Mustache(null, array('result' => 'FAIL'));
		$this->assertEquals('{{ result }}', $m->render('{{=[[ ]]=}}{{ result }}[[={{ }}=]]'));
		$this->assertEquals('{{#result}}{{/result}}', $m->render('{{=[[ ]]=}}{{#result}}{{/result}}[[={{ }}=]]'));
		$this->assertEquals('{{ result }}', $m->render('{{=[[ ]]=}}[[#result]]{{ result }}[[/result]][[={{ }}=]]'));
		$this->assertEquals('{{ result }}', $m->render('{{#result}}{{=[[ ]]=}}{{ result }}[[/result]][[^result]][[={{ }}=]][[ result ]]{{/result}}'));
	}

	/**
	 * @group sections
	 * @dataProvider poorlyNestedSections
	 * @expectedException MustacheException
	 */
	public function testPoorlyNestedSections($template) {
		$m = new Mustache($template);
		$m->render();
	}

	public function poorlyNestedSections() {
		return array(
			array('{{#foo}}'),
			array('{{#foo}}{{/bar}}'),
			array('{{#foo}}{{#bar}}{{/foo}}'),
			array('{{#foo}}{{#bar}}{{/foo}}{{/bar}}'),
			array('{{#foo}}{{/bar}}{{/foo}}'),
		);
	}

	/**
	 * Ensure that Mustache doesn't double-render sections (allowing mustache injection).
	 *
	 * @group sections
	 */
	public function testMustacheInjection() {
		$template = '{{#foo}}{{bar}}{{/foo}}';
		$view = array(
			'foo' => true,
			'bar' => '{{win}}',
			'win' => 'FAIL',
		);

		$m = new Mustache($template, $view);
		$this->assertEquals('{{win}}', $m->render());
	}
}

class MustacheExposedOptionsStub extends Mustache {
	public function getPragmas() {
		return $this->_pragmas;
	}
	public function getCharset() {
		return $this->_charset;
	}
	public function getDelimiters() {
		return array($this->_otag, $this->_ctag);
	}
}
