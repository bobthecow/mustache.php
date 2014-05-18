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
class ParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider getTokenSets
     */
    public function testParse($tokens, $expected)
    {
        $parser = new \Mustache\Parser;
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
                array(array(
                    \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                    \Mustache\Tokenizer::LINE  => 0,
                    \Mustache\Tokenizer::VALUE => 'text',
                )),
                array(array(
                    \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                    \Mustache\Tokenizer::LINE  => 0,
                    \Mustache\Tokenizer::VALUE => 'text',
                )),
            ),

            array(
                array(array(
                    \Mustache\Tokenizer::TYPE => \Mustache\Tokenizer::T_ESCAPED,
                    \Mustache\Tokenizer::LINE => 0,
                    \Mustache\Tokenizer::NAME => 'name'
                )),
                array(array(
                    \Mustache\Tokenizer::TYPE => \Mustache\Tokenizer::T_ESCAPED,
                    \Mustache\Tokenizer::LINE => 0,
                    \Mustache\Tokenizer::NAME => 'name'
                )),
            ),

            array(
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::VALUE => 'foo'
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_INVERTED,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 123,
                        \Mustache\Tokenizer::NAME  => 'parent'
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_ESCAPED,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::NAME  => 'name'
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_END_SECTION,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 456,
                        \Mustache\Tokenizer::NAME  => 'parent'
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::VALUE => 'bar'
                    ),
                ),
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::VALUE => 'foo'
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_INVERTED,
                        \Mustache\Tokenizer::NAME  => 'parent',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 123,
                        \Mustache\Tokenizer::END   => 456,
                        \Mustache\Tokenizer::NODES => array(
                            array(
                                \Mustache\Tokenizer::TYPE => \Mustache\Tokenizer::T_ESCAPED,
                                \Mustache\Tokenizer::LINE => 0,
                                \Mustache\Tokenizer::NAME => 'name'
                            ),
                        ),
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_TEXT,
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::VALUE => 'bar'
                    ),
                ),
            ),

        );
    }

    /**
     * @dataProvider getBadParseTrees
     * @expectedException \Mustache\Exception\SyntaxException
     */
    public function testParserThrowsExceptions($tokens)
    {
        $parser = new \Mustache\Parser;
        $parser->parse($tokens);
    }

    public function getBadParseTrees()
    {
        return array(
            // no close
            array(
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_SECTION,
                        \Mustache\Tokenizer::NAME  => 'parent',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 123,
                    ),
                ),
            ),

            // no close inverted
            array(
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_INVERTED,
                        \Mustache\Tokenizer::NAME  => 'parent',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 123,
                    ),
                ),
            ),

            // no opening inverted
            array(
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_END_SECTION,
                        \Mustache\Tokenizer::NAME  => 'parent',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 123,
                    ),
                ),
            ),

            // weird nesting
            array(
                array(
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_SECTION,
                        \Mustache\Tokenizer::NAME  => 'parent',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 123,
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_SECTION,
                        \Mustache\Tokenizer::NAME  => 'child',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 123,
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_END_SECTION,
                        \Mustache\Tokenizer::NAME  => 'parent',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 123,
                    ),
                    array(
                        \Mustache\Tokenizer::TYPE  => \Mustache\Tokenizer::T_END_SECTION,
                        \Mustache\Tokenizer::NAME  => 'child',
                        \Mustache\Tokenizer::LINE  => 0,
                        \Mustache\Tokenizer::INDEX => 123,
                    ),
                ),
            ),
        );
    }
}
