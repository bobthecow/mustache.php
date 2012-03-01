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
class Mustache_Test_ParserTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getTokenSets
	 */
	public function testParse($tokens, $expected)
	{
		$parser = new Mustache_Parser;
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
					Mustache_Tokenizer::TAG => '_v',
					Mustache_Tokenizer::NAME => 'name'
				)),
				array(array(
					Mustache_Tokenizer::TAG => '_v',
					Mustache_Tokenizer::NAME => 'name'
				)),
			),

			array(
				array(
					'foo',
					array(
						Mustache_Tokenizer::TAG => '^',
						Mustache_Tokenizer::INDEX => 123,
						Mustache_Tokenizer::NAME => 'parent'
					),
					array(
						Mustache_Tokenizer::TAG => '_v',
						Mustache_Tokenizer::NAME => 'name'
					),
					array(
						Mustache_Tokenizer::TAG => '/',
						Mustache_Tokenizer::INDEX => 456,
						Mustache_Tokenizer::NAME => 'parent'
					),
					'bar',
				),
				array(
					'foo',
					array(
						Mustache_Tokenizer::TAG => '^',
						Mustache_Tokenizer::NAME => 'parent',
						Mustache_Tokenizer::INDEX => 123,
						Mustache_Tokenizer::END => 456,
						Mustache_Tokenizer::NODES => array(
							array(
								Mustache_Tokenizer::TAG => '_v',
								Mustache_Tokenizer::NAME => 'name'
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
		$parser = new Mustache_Parser;
		$parser->parse($tokens);
	}

	public function getBadParseTrees() {
		return array(
			// no close
			array(
				array(
					array(
						Mustache_Tokenizer::TAG => '#',
						Mustache_Tokenizer::INDEX => 123,
						Mustache_Tokenizer::NAME => 'parent'
					),
				),
			),

			// no close inverted
			array(
				array(
					array(
						Mustache_Tokenizer::TAG => '^',
						Mustache_Tokenizer::INDEX => 123,
						Mustache_Tokenizer::NAME => 'parent'
					),
				),
			),

			// no opening inverted
			array(
				array(
					array(
						Mustache_Tokenizer::TAG => '/',
						Mustache_Tokenizer::INDEX => 123,
						Mustache_Tokenizer::NAME => 'parent'
					),
				),
			),

			// weird nesting
			array(
				array(
					array(
						Mustache_Tokenizer::TAG => '#',
						Mustache_Tokenizer::INDEX => 123,
						Mustache_Tokenizer::NAME => 'parent'
					),
					array(
						Mustache_Tokenizer::TAG => '#',
						Mustache_Tokenizer::INDEX => 123,
						Mustache_Tokenizer::NAME => 'child'
					),
					array(
						Mustache_Tokenizer::TAG => '/',
						Mustache_Tokenizer::INDEX => 123,
						Mustache_Tokenizer::NAME => 'parent'
					),
					array(
						Mustache_Tokenizer::TAG => '/',
						Mustache_Tokenizer::INDEX => 123,
						Mustache_Tokenizer::NAME => 'child'
					),
				),
			),
		);
	}
}
