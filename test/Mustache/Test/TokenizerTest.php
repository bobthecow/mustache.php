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

use Mustache\Tokenizer;

/**
 * @group unit
 */
class TokenizerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getTokens
	 */
	public function testScan($text, $delimiters, $expected) {
		$tokenizer = new Tokenizer;
		$this->assertSame($expected, $tokenizer->scan($text, $delimiters));
	}

	public function getTokens() {
		return array(
			array(
				'text',
				null,
				array('text'),
			),

			array(
				'text',
				'<<< >>>',
				array('text'),
			),

			array(
				'{{ name }}',
				null,
				array(
					array(
						Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
						Tokenizer::NAME  => 'name',
						Tokenizer::OTAG  => '{{',
						Tokenizer::CTAG  => '}}',
						Tokenizer::INDEX => 10,
					)
				)
			),

			array(
				'{{ name }}',
				'<<< >>>',
				array('{{ name }}'),
			),

			array(
				'<<< name >>>',
				'<<< >>>',
				array(
					array(
						Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
						Tokenizer::NAME  => 'name',
						Tokenizer::OTAG  => '<<<',
						Tokenizer::CTAG  => '>>>',
						Tokenizer::INDEX => 12,
					)
				)
			),

			array(
				"{{{ a }}}\n{{# b }}  \n{{= | | =}}| c ||/ b |\n|{ d }|",
				null,
				array(
					array(
						Tokenizer::TYPE  => Tokenizer::T_UNESCAPED,
						Tokenizer::NAME  => 'a',
						Tokenizer::OTAG  => '{{',
						Tokenizer::CTAG  => '}}',
						Tokenizer::INDEX => 8,
					),
					"\n",
					array(
						Tokenizer::TYPE  => Tokenizer::T_SECTION,
						Tokenizer::NAME  => 'b',
						Tokenizer::OTAG  => '{{',
						Tokenizer::CTAG  => '}}',
						Tokenizer::INDEX => 18,
					),
					null,
					array(
						Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
						Tokenizer::NAME  => 'c',
						Tokenizer::OTAG  => '|',
						Tokenizer::CTAG  => '|',
						Tokenizer::INDEX => 37,
					),
					array(
						Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
						Tokenizer::NAME  => 'b',
						Tokenizer::OTAG  => '|',
						Tokenizer::CTAG  => '|',
						Tokenizer::INDEX => 37,
					),
					"\n",
					array(
						Tokenizer::TYPE  => Tokenizer::T_UNESCAPED,
						Tokenizer::NAME  => 'd',
						Tokenizer::OTAG  => '|',
						Tokenizer::CTAG  => '|',
						Tokenizer::INDEX => 51,
					),

				)
			),
		);
	}
}
