<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2015 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group unit
 */
class Mustache_Test_ParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTokenSets
     */
    public function testParse($tokens, $expected)
    {
        $parser = new Mustache_Parser();
        $this->assertEquals($expected, $parser->parse($tokens));
    }

    public function getTokenSets()
    {
        return array(
            array(
                array(),
                array(),
            ),

            array(
                array(array(
                    Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                    Mustache_Tokenizer::LINE  => 0,
                    Mustache_Tokenizer::VALUE => 'text',
                )),
                array(array(
                    Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                    Mustache_Tokenizer::LINE  => 0,
                    Mustache_Tokenizer::VALUE => 'text',
                )),
            ),

            array(
                array(array(
                    Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_ESCAPED,
                    Mustache_Tokenizer::LINE => 0,
                    Mustache_Tokenizer::NAME => 'name',
                )),
                array(array(
                    Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_ESCAPED,
                    Mustache_Tokenizer::LINE => 0,
                    Mustache_Tokenizer::NAME => 'name',
                )),
            ),

            array(
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => 'foo',
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_INVERTED,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 123,
                        Mustache_Tokenizer::NAME  => 'parent',
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::NAME  => 'name',
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_END_SECTION,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 456,
                        Mustache_Tokenizer::NAME  => 'parent',
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => 'bar',
                    ),
                ),

                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => 'foo',
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_INVERTED,
                        Mustache_Tokenizer::NAME  => 'parent',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 123,
                        Mustache_Tokenizer::END   => 456,
                        Mustache_Tokenizer::NODES => array(
                            array(
                                Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_ESCAPED,
                                Mustache_Tokenizer::LINE => 0,
                                Mustache_Tokenizer::NAME => 'name',
                            ),
                        ),
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => 'bar',
                    ),
                ),
            ),

            // This *would* be an invalid inheritance parse tree, but that pragma
            // isn't enabled so it'll thunk it back into an "escaped" token:
            array(
                array(
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_BLOCK_VAR,
                        Mustache_Tokenizer::NAME => 'foo',
                        Mustache_Tokenizer::OTAG => '{{',
                        Mustache_Tokenizer::CTAG => '}}',
                        Mustache_Tokenizer::LINE => 0,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => 'bar',
                    ),
                ),
                array(
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME => '$foo',
                        Mustache_Tokenizer::OTAG => '{{',
                        Mustache_Tokenizer::CTAG => '}}',
                        Mustache_Tokenizer::LINE => 0,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => 'bar',
                    ),
                ),
            ),

            array(
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => '  ',
                    ),
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_DELIM_CHANGE,
                        Mustache_Tokenizer::LINE => 0,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => "  \n",
                    ),
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME => 'foo',
                        Mustache_Tokenizer::OTAG => '[[',
                        Mustache_Tokenizer::CTAG => ']]',
                        Mustache_Tokenizer::LINE => 1,
                    ),
                ),
                array(
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME => 'foo',
                        Mustache_Tokenizer::OTAG => '[[',
                        Mustache_Tokenizer::CTAG => ']]',
                        Mustache_Tokenizer::LINE => 1,
                    ),
                ),
            ),

        );
    }

    /**
     * @dataProvider getInheritanceTokenSets
     */
    public function testParseWithInheritance($tokens, $expected)
    {
        $parser = new Mustache_Parser();
        $parser->setPragmas(array(Mustache_Engine::PRAGMA_BLOCKS));
        $this->assertEquals($expected, $parser->parse($tokens));
    }

    public function getInheritanceTokenSets()
    {
        return array(
            array(
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_PARENT,
                        Mustache_Tokenizer::NAME  => 'foo',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 8,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_BLOCK_VAR,
                        Mustache_Tokenizer::NAME  => 'bar',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 16,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => 'baz',
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_END_SECTION,
                        Mustache_Tokenizer::NAME  => 'bar',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 19,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_END_SECTION,
                        Mustache_Tokenizer::NAME  => 'foo',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 27,
                    ),
                ),
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_PARENT,
                        Mustache_Tokenizer::NAME  => 'foo',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 8,
                        Mustache_Tokenizer::END   => 27,
                        Mustache_Tokenizer::NODES => array(
                            array(
                                Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_BLOCK_ARG,
                                Mustache_Tokenizer::NAME  => 'bar',
                                Mustache_Tokenizer::OTAG  => '{{',
                                Mustache_Tokenizer::CTAG  => '}}',
                                Mustache_Tokenizer::LINE  => 0,
                                Mustache_Tokenizer::INDEX => 16,
                                Mustache_Tokenizer::END   => 19,
                                Mustache_Tokenizer::NODES => array(
                                    array(
                                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                                        Mustache_Tokenizer::LINE  => 0,
                                        Mustache_Tokenizer::VALUE => 'baz',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),

            array(
                array(
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_BLOCK_VAR,
                        Mustache_Tokenizer::NAME => 'foo',
                        Mustache_Tokenizer::OTAG => '{{',
                        Mustache_Tokenizer::CTAG => '}}',
                        Mustache_Tokenizer::LINE => 0,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => 'bar',
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_END_SECTION,
                        Mustache_Tokenizer::NAME  => 'foo',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 11,
                    ),
                ),
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_BLOCK_VAR,
                        Mustache_Tokenizer::NAME  => 'foo',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::END   => 11,
                        Mustache_Tokenizer::NODES => array(
                            array(
                                Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                                Mustache_Tokenizer::LINE  => 0,
                                Mustache_Tokenizer::VALUE => 'bar',
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider getBadParseTrees
     * @expectedException Mustache_Exception_SyntaxException
     */
    public function testParserThrowsExceptions($tokens)
    {
        $parser = new Mustache_Parser();
        $parser->parse($tokens);
    }

    public function getBadParseTrees()
    {
        return array(
            // no close
            array(
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_SECTION,
                        Mustache_Tokenizer::NAME  => 'parent',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 123,
                    ),
                ),
            ),

            // no close inverted
            array(
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_INVERTED,
                        Mustache_Tokenizer::NAME  => 'parent',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 123,
                    ),
                ),
            ),

            // no opening inverted
            array(
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_END_SECTION,
                        Mustache_Tokenizer::NAME  => 'parent',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 123,
                    ),
                ),
            ),

            // weird nesting
            array(
                array(
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_SECTION,
                        Mustache_Tokenizer::NAME  => 'parent',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 123,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_SECTION,
                        Mustache_Tokenizer::NAME  => 'child',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 123,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_END_SECTION,
                        Mustache_Tokenizer::NAME  => 'parent',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 123,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_END_SECTION,
                        Mustache_Tokenizer::NAME  => 'child',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 123,
                    ),
                ),
            ),

            // This *would* be a valid inheritance parse tree, but that pragma
            // isn't enabled here so it's going to fail :)
            array(
                array(
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_BLOCK_VAR,
                        Mustache_Tokenizer::NAME => 'foo',
                        Mustache_Tokenizer::OTAG => '{{',
                        Mustache_Tokenizer::CTAG => '}}',
                        Mustache_Tokenizer::LINE => 0,
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::VALUE => 'bar',
                    ),
                    array(
                        Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_END_SECTION,
                        Mustache_Tokenizer::NAME  => 'foo',
                        Mustache_Tokenizer::OTAG  => '{{',
                        Mustache_Tokenizer::CTAG  => '}}',
                        Mustache_Tokenizer::LINE  => 0,
                        Mustache_Tokenizer::INDEX => 11,
                    ),
                ),
            ),
        );
    }
}
