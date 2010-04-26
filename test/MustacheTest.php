<?php

require_once '../Mustache.php';
require_once 'PHPUnit/Framework.php';

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

	/**
	 * Test everything in the `examples` directory.
	 *
	 * @dataProvider getExamples
	 * @access public
	 * @param mixed $class
	 * @param mixed $template
	 * @param mixed $output
	 * @return void
	 */
	public function testExamples($class, $template, $output) {
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
	 * @access public
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

					$filename = $file->getPathInfo();
					$info = pathinfo($filename);

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

				$ret[$example] = array($class, $template, $output);
			}

			$files->next();
		}
		return $ret;
	}
}