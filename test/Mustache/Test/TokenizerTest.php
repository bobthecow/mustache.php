<?php
namespace Mustache\Test;

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group unit
 */
class TokenizerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider getTokens
     */
    public function testScan($text, $delimiters, $expected)
    {
        $tokenizer = new \Mustache\Tokenizer;
        $this->assertSame($expected, $tokenizer->scan($text, $delimiters));
    }

    public function getTokens()
    {
        return array(
            array(
                'text',
                null,
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::VALUE => 'text',
                    ),
                ),
            ),

            array(
                'text',
                '<<< >>>',
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::VALUE => 'text',
                    ),
                ),
            ),

            array(
                '{{ name }}',
                null,
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_ESCAPED,
                        \Mustache\Tokenizer::NAME  => 'name',
                        \Mustache\Tokenizer::OTAG  => '{{',
                        \Mustache\Tokenizer::CTAG  => '}}',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 10,
                    )
                )
            ),

            array(
                '{{ name }}',
                '<<< >>>',
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::VALUE => '{{ name }}',
                    ),
                ),
            ),

            array(
                '<<< name >>>',
                '<<< >>>',
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_ESCAPED,
                        \Mustache\Tokenizer::NAME  => 'name',
                        \Mustache\Tokenizer::OTAG  => '<<<',
                        \Mustache\Tokenizer::CTAG  => '>>>',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 12,
                    )
                )
            ),

            array(
                "{{{ a }}}\n{{# b }}  \n{{= | | =}}| c ||/ b |\n|{ d }|",
                null,
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_UNESCAPED,
                        \Mustache\Tokenizer::NAME  => 'a',
                        \Mustache\Tokenizer::OTAG  => '{{',
                        \Mustache\Tokenizer::CTAG  => '}}',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 8,
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::VALUE => "\n",
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_SECTION,
                        \Mustache\Tokenizer::NAME  => 'b',
                        \Mustache\Tokenizer::OTAG  => '{{',
                        \Mustache\Tokenizer::CTAG  => '}}',
                        \Mustache\Tokenizer::LINE  => 1,
                        \Mustache\Tokenizer::INDEX => 18,
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                        \Mustache\Tokenizer::LINE  => 1,
                        \Mustache\Tokenizer::VALUE => "  \n",
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_DELIM_CHANGE,
                        \Mustache\Tokenizer::LINE  => 2,
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_ESCAPED,
                        \Mustache\Tokenizer::NAME  => 'c',
                        \Mustache\Tokenizer::OTAG  => '|',
                        \Mustache\Tokenizer::CTAG  => '|',
                        \Mustache\Tokenizer::LINE  => 2,
                        \Mustache\Tokenizer::INDEX => 37,
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_END_SECTION,
                        \Mustache\Tokenizer::NAME  => 'b',
                        \Mustache\Tokenizer::OTAG  => '|',
                        \Mustache\Tokenizer::CTAG  => '|',
                        \Mustache\Tokenizer::LINE  => 2,
                        \Mustache\Tokenizer::INDEX => 37,
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                        \Mustache\Tokenizer::LINE  => 2,
                        \Mustache\Tokenizer::VALUE => "\n",
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_UNESCAPED,
                        \Mustache\Tokenizer::NAME  => 'd',
                        \Mustache\Tokenizer::OTAG  => '|',
                        \Mustache\Tokenizer::CTAG  => '|',
                        \Mustache\Tokenizer::LINE  => 3,
                        \Mustache\Tokenizer::INDEX => 51,
                    ),

                )
            ),

            // See https://github.com/bobthecow/mustache.php/issues/183
            array(
                "{{# a }}0{{/ a }}",
                null,
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_SECTION,
                        \Mustache\Tokenizer::NAME  => 'a',
                        \Mustache\Tokenizer::OTAG  => '{{',
                        \Mustache\Tokenizer::CTAG  => '}}',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 8,
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::VALUE => "0",
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_END_SECTION,
                        \Mustache\Tokenizer::NAME  => 'a',
                        \Mustache\Tokenizer::OTAG  => '{{',
                        \Mustache\Tokenizer::CTAG  => '}}',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 9,
                    ),
                )
            ),
        );
    }
}
