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
use Mustache\Tokenizer;

/**
 * @group unit
 */
class CompilerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getCompileValues
	 */
	public function testCompile($source, array $tree, $name, $expected) {
		$compiler = new Compiler;

		$compiled = $compiler->compile($source, $tree, $name);
		foreach ($expected as $contains) {
			$this->assertContains($contains, $compiled);
		}
	}

	public function getCompileValues() {
		return array(
			array('', array(), 'Banana', array(
				"\nclass Banana extends \Mustache\Template",
				'return $buffer;',
			)),

			array('', array('TEXT'), 'Monkey', array(
				"\nclass Monkey extends \Mustache\Template",
				'$buffer .= $indent . \'TEXT\';',
				'return $buffer;',
			)),

			array(
				'',
				array(
					'foo',
					"\n",
					array(
						Tokenizer::TYPE => Tokenizer::T_ESCAPED,
						Tokenizer::NAME => 'name',
					),
					array(
						Tokenizer::TYPE => Tokenizer::T_ESCAPED,
						Tokenizer::NAME => '.',
					),
					"'bar'",
				),
				'Monkey',
				array(
					"\nclass Monkey extends \Mustache\Template",
					'$buffer .= $indent . \'foo\'',
					'$buffer .= "\n"',
					'$value = $context->find(\'name\');',
					'$buffer .= htmlspecialchars($value, ENT_COMPAT, $this->mustache->getCharset());',
					'$value = $context->last();',
					'$buffer .= \'\\\'bar\\\'\';',
					'return $buffer;',
				)
			),
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testCompilerThrowsUnknownNodeTypeException() {
		$compiler = new Compiler;
		$compiler->compile('', array(array(Tokenizer::TYPE => 'invalid')), 'SomeClass');
	}
}
