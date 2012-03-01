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
				'$buffer->flush();',
			)),

			array('', array('TEXT'), 'Monkey', array(
				"\nclass Monkey extends \Mustache\Template",
				'$buffer->writeText(\'TEXT\');',
				'$buffer->flush();',
			)),

			array(
				'',
				array(
					'foo',
					"\n",
					array(
						Tokenizer::TAG  => '_v',
						Tokenizer::NAME => 'name',
					),
					array(
						Tokenizer::TAG  => '_v',
						Tokenizer::NAME => '.',
					),
					"'bar'",
				),
				'Monkey',
				array(
					"\nclass Monkey extends \Mustache\Template",
					'$buffer->writeText(\'foo\');',
					'$buffer->writeLine();',
					'$value = $context->find(\'name\');',
					'$buffer->writeText($value, true);',
					'$value = $context->last();',
					'$buffer->writeText(\'\\\'bar\\\'\');',
					'$buffer->flush();',
				)
			),
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testCompilerThrowsUnknownNodeTypeException() {
		$compiler = new Compiler;
		$compiler->compile('', array(array(Tokenizer::TAG => 'invalid')), 'SomeClass');
	}
}
