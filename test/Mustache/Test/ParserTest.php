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

use Mustache\Parser;
use Mustache\Tokenizer;

/**
 * @group unit
 */
class ParserTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getTokenSets
	 */
	public function testParse($tokens, $expected)
	{
		$parser = new Parser;
		$this->assertEquals($expected, $parser->parse($tokens));
	}

	public function getTokenSets()
	{
		return array(
			array(
				array(),
				array()
			),

			array(
				array('text'),
				array('text')
			),

			array(
				array(array(
					Tokenizer::TYPE => Tokenizer::T_ESCAPED,
					Tokenizer::NAME => 'name'
				)),
				array(array(
					Tokenizer::TYPE => Tokenizer::T_ESCAPED,
					Tokenizer::NAME => 'name'
				)),
			),

			array(
				array(
					'foo',
					array(
						Tokenizer::TYPE  => Tokenizer::T_INVERTED,
						Tokenizer::INDEX => 123,
						Tokenizer::NAME  => 'parent'
					),
					array(
						Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
						Tokenizer::NAME  => 'name'
					),
					array(
						Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
						Tokenizer::INDEX => 456,
						Tokenizer::NAME  => 'parent'
					),
					'bar',
				),
				array(
					'foo',
					array(
						Tokenizer::TYPE  => Tokenizer::T_INVERTED,
						Tokenizer::NAME  => 'parent',
						Tokenizer::INDEX => 123,
						Tokenizer::END   => 456,
						Tokenizer::NODES => array(
							array(
								Tokenizer::TYPE => Tokenizer::T_ESCAPED,
								Tokenizer::NAME => 'name'
							),
						),
					),
					'bar',
				),
			),

		);
	}

	/**
	 * @dataProvider getBadParseTrees
	 * @expectedException \LogicException
	 */
	public function testParserThrowsExceptions($tokens) {
		$parser = new Parser;
		$parser->parse($tokens);
	}

	public function getBadParseTrees() {
		return array(
			// no close
			array(
				array(
					array(
						Tokenizer::TYPE  => Tokenizer::T_SECTION,
						Tokenizer::NAME  => 'parent',
						Tokenizer::INDEX => 123,
					),
				),
			),

			// no close inverted
			array(
				array(
					array(
						Tokenizer::TYPE  => Tokenizer::T_INVERTED,
						Tokenizer::NAME  => 'parent',
						Tokenizer::INDEX => 123,
					),
				),
			),

			// no opening inverted
			array(
				array(
					array(
						Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
						Tokenizer::NAME  => 'parent',
						Tokenizer::INDEX => 123,
					),
				),
			),

			// weird nesting
			array(
				array(
					array(
						Tokenizer::TYPE  => Tokenizer::T_SECTION,
						Tokenizer::NAME  => 'parent',
						Tokenizer::INDEX => 123,
					),
					array(
						Tokenizer::TYPE  => Tokenizer::T_SECTION,
						Tokenizer::NAME  => 'child',
						Tokenizer::INDEX => 123,
					),
					array(
						Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
						Tokenizer::NAME  => 'parent',
						Tokenizer::INDEX => 123,
					),
					array(
						Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
						Tokenizer::NAME  => 'child',
						Tokenizer::INDEX => 123,
					),
				),
			),
		);
	}
}
