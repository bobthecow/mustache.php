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
class Mustache_Test_CompilerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getCompileValues
	 */
	public function testCompile($source, array $tree, $name, $expected) {
		$compiler = new Mustache_Compiler;

		$compiled = $compiler->compile($source, $tree, $name);
		foreach ($expected as $contains) {
			$this->assertContains($contains, $compiled);
		}
	}

	public function getCompileValues() {
		return array(
			array('', array(), 'Banana', array(
				"\nclass Banana extends Mustache_Template",
				'$buffer->flush();',
			)),

			array('', array('TEXT'), 'Monkey', array(
				"\nclass Monkey extends Mustache_Template",
				'$buffer->writeText(\'TEXT\');',
				'$buffer->flush();',
			)),

			array(
				'',
				array(
					'foo',
					"\n",
					array(
						Mustache_Tokenizer::TAG  => '_v',
						Mustache_Tokenizer::NAME => 'name',
					),
					array(
						Mustache_Tokenizer::TAG  => '_v',
						Mustache_Tokenizer::NAME => '.',
					),
					"'bar'",
				),
				'Monkey',
				array(
					"\nclass Monkey extends Mustache_Template",
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
		$compiler = new Mustache_Compiler;
		$compiler->compile('', array(array(Mustache_Tokenizer::TAG => 'invalid')), 'SomeClass');
	}
}
