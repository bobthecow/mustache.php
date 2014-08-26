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
        $tokenizer = new Mustache_Tokenizer();
        $this->assertSame($expected, $tokenizer->scan($text, $delimiters));
    }

    /**
     * @expectedException Mustache_Exception_SyntaxException
     */
    public function testUnevenBracesThrowExceptions()
    {
        $tokenizer = new Mustache_Tokenizer();

        $text = "{{{ name }}";
        $tokenizer->scan($text, null);
    }

    /**
     * @expectedException Mustache_Exception_SyntaxException
     */
    public function testUnevenBracesWithCustomDelimiterThrowExceptions()
    {
        $tokenizer = new Mustache_Tokenizer();

        $text = "<%{ name %>";
        $tokenizer->scan($text, "<% %>");
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

            // custom delimiters don't swallow the next character, even if it is a }, }}}, or the same delimiter
            array(
                "<% a %>} <% b %>%> <% c %>}}}",
                "<% %>",
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME  => 'a',
                        Mustache_Tokenizer::OTAG  => '<%',
                        Mustache_Tokenizer::CTAG  => '%>',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 7,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => "} ",
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME  => 'b',
                        Mustache_Tokenizer::OTAG  => '<%',
                        Mustache_Tokenizer::CTAG  => '%>',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 16,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => "%> ",
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME  => 'c',
                        Mustache_Tokenizer::OTAG  => '<%',
                        Mustache_Tokenizer::CTAG  => '%>',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 26,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => "}}}",
                    ),
                )
            ),

            // unescaped custom delimiters are properly parsed
            array(
                "<%{ a }%>",
                "<% %>",
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_UNESCAPED,
                        Mustache_Tokenizer::NAME  => 'a',
                        Mustache_Tokenizer::OTAG  => '<%',
                        Mustache_Tokenizer::CTAG  => '%>',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 9,
                    )
                )
            ),

            // Ensure that $arg token is not picked up during tokenization
            array(
                '{{$arg}}default{{/arg}}',
                null,
                array(
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_BLOCK_VAR,
                        Mustache_Tokenizer::NAME => 'arg',
                        Mustache_Tokenizer::OTAG => '{{',
                        Mustache_Tokenizer::CTAG => '}}',
                        Mustache_Tokenizer::LINE => 0,
                        Mustache_Tokenizer::INDEX => 8
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => "default",
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_END_SECTION,
                        Mustache_Tokenizer::NAME  => 'arg',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 15,
                    )
                )
            ),
        );
    }
}
