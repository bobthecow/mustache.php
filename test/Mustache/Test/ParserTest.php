<?php

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
					Tokenizer::TAG => '_v',
					Tokenizer::NAME => 'name'
				)),
				array(array(
					Tokenizer::TAG => '_v',
					Tokenizer::NAME => 'name'
				)),
			),

			array(
				array(
					'foo',
					array(
						Tokenizer::TAG => '^',
						Tokenizer::INDEX => 123,
						Tokenizer::NAME => 'parent'
					),
					array(
						Tokenizer::TAG => '_v',
						Tokenizer::NAME => 'name'
					),
					array(
						Tokenizer::TAG => '/',
						Tokenizer::INDEX => 456,
						Tokenizer::NAME => 'parent'
					),
					'bar',
				),
				array(
					'foo',
					array(
						Tokenizer::TAG => '^',
						Tokenizer::NAME => 'parent',
						Tokenizer::INDEX => 123,
						Tokenizer::END => 456,
						Tokenizer::NODES => array(
							array(
								Tokenizer::TAG => '_v',
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
						Tokenizer::TAG => '#',
						Tokenizer::INDEX => 123,
						Tokenizer::NAME => 'parent'
					),
				),
			),

			// no close inverted
			array(
				array(
					array(
						Tokenizer::TAG => '^',
						Tokenizer::INDEX => 123,
						Tokenizer::NAME => 'parent'
					),
				),
			),

			// no opening inverted
			array(
				array(
					array(
						Tokenizer::TAG => '/',
						Tokenizer::INDEX => 123,
						Tokenizer::NAME => 'parent'
					),
				),
			),

			// weird nesting
			array(
				array(
					array(
						Tokenizer::TAG => '#',
						Tokenizer::INDEX => 123,
						Tokenizer::NAME => 'parent'
					),
					array(
						Tokenizer::TAG => '#',
						Tokenizer::INDEX => 123,
						Tokenizer::NAME => 'child'
					),
					array(
						Tokenizer::TAG => '/',
						Tokenizer::INDEX => 123,
						Tokenizer::NAME => 'parent'
					),
					array(
						Tokenizer::TAG => '/',
						Tokenizer::INDEX => 123,
						Tokenizer::NAME => 'child'
					),
				),
			),
		);
	}
}
