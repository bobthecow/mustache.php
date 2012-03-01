<?php

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
						Tokenizer::TAG   => '_v',
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
						Tokenizer::TAG   => '_v',
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
						Tokenizer::TAG   => '{',
						Tokenizer::NAME  => 'a',
						Tokenizer::OTAG  => '{{',
						Tokenizer::CTAG  => '}}',
						Tokenizer::INDEX => 8,
					),
					"\n",
					array(
						Tokenizer::TAG   => '#',
						Tokenizer::NAME  => 'b',
						Tokenizer::OTAG  => '{{',
						Tokenizer::CTAG  => '}}',
						Tokenizer::INDEX => 18,
					),
					null,
					array(
						Tokenizer::TAG   => '_v',
						Tokenizer::NAME  => 'c',
						Tokenizer::OTAG  => '|',
						Tokenizer::CTAG  => '|',
						Tokenizer::INDEX => 37,
					),
					array(
						Tokenizer::TAG   => '/',
						Tokenizer::NAME  => 'b',
						Tokenizer::OTAG  => '|',
						Tokenizer::CTAG  => '|',
						Tokenizer::INDEX => 37,
					),
					"\n",
					array(
						Tokenizer::TAG   => '{',
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
