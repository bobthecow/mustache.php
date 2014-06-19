<?php

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
class Mustache_Test_TokenizerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider getTokens
     */
    public function testScan($text, $delimiters, $expected)
    {
        $tokenizer = new Mustache_Tokenizer;
        $this->assertSame($expected, $tokenizer->scan($text, $delimiters));
    }

    /**
     * @expectedException Mustache_Exception_SyntaxException
     */
    public function testUnevenBracesThrowExceptions()
    {
        $tokenizer = new Mustache_Tokenizer;

        $text = "{{{ name }}";
        $tokenizer->scan($text, null);
    }

    public function getTokens()
    {
        return array(
            array(
                'text',
                null,
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => 'text',
                    ),
                ),
            ),

            array(
                'text',
                '<<< >>>',
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => 'text',
                    ),
                ),
            ),

            array(
                '{{ name }}',
                null,
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME  => 'name',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 10,
                    )
                )
            ),

            array(
                '{{ name }}',
                '<<< >>>',
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => '{{ name }}',
                    ),
                ),
            ),

            array(
                '<<< name >>>',
                '<<< >>>',
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME  => 'name',
                        Mustache_Tokenizer::OTAG  => '<<<',
                        Mustache_Tokenizer::CTAG  => '>>>',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 12,
                    )
                )
            ),

            array(
                "{{{ a }}}\n{{# b }}  \n{{= | | =}}| c ||/ b |\n|{ d }|",
                null,
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_UNESCAPED,
                        Mustache_Tokenizer::NAME  => 'a',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 8,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => "\n",
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_SECTION,
                        Mustache_Tokenizer::NAME  => 'b',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 1,
                        Mustache_Tokenizer::INDEX => 18,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 1,
                        Mustache_Tokenizer::VALUE => "  \n",
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_DELIM_CHANGE,
                        Mustache_Tokenizer::LINE  => 2,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME  => 'c',
                        Mustache_Tokenizer::OTAG  => '|',
                        Mustache_Tokenizer::CTAG  => '|',
                        Mustache_Tokenizer::LINE  => 2,
                        Mustache_Tokenizer::INDEX => 37,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_END_SECTION,
                        Mustache_Tokenizer::NAME  => 'b',
                        Mustache_Tokenizer::OTAG  => '|',
                        Mustache_Tokenizer::CTAG  => '|',
                        Mustache_Tokenizer::LINE  => 2,
                        Mustache_Tokenizer::INDEX => 37,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 2,
                        Mustache_Tokenizer::VALUE => "\n",
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_UNESCAPED,
                        Mustache_Tokenizer::NAME  => 'd',
                        Mustache_Tokenizer::OTAG  => '|',
                        Mustache_Tokenizer::CTAG  => '|',
                        Mustache_Tokenizer::LINE  => 3,
                        Mustache_Tokenizer::INDEX => 51,
                    ),

                )
            ),

            // See https://github.com/bobthecow/mustache.php/issues/183
            array(
                "{{# a }}0{{/ a }}",
                null,
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_SECTION,
                        Mustache_Tokenizer::NAME  => 'a',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 8,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => "0",
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_END_SECTION,
                        Mustache_Tokenizer::NAME  => 'a',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 9,
                    ),
                )
            ),
        );
    }
}
