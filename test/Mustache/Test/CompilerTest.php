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
class Mustache_Test_CompilerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider getCompileValues
     */
    public function testCompile($source, array $tree, $name, $customEscaper, $charset, $expected)
    {
        $compiler = new Mustache_Compiler;

        $compiled = $compiler->compile($source, $tree, $name, $customEscaper, $charset);
        foreach ($expected as $contains) {
            $this->assertContains($contains, $compiled);
        }
    }

    public function getCompileValues()
    {
        return array(
            array('', array(), 'Banana', false, 'ISO-8859-1', array(
                "\nclass Banana extends Mustache_Template",
                'return $buffer;',
            )),

            array('', array($this->createTextToken('TEXT')), 'Monkey', false, 'UTF-8', array(
                "\nclass Monkey extends Mustache_Template",
                '$buffer .= $indent . \'TEXT\';',
                'return $buffer;',
            )),

            array(
                '',
                array(
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME => 'name',
                    )
                ),
                'Monkey',
                true,
                'ISO-8859-1',
                array(
                    "\nclass Monkey extends Mustache_Template",
                    '$value = $this->resolveValue($context->find(\'name\'), $context, $indent);',
                    '$buffer .= $indent . call_user_func($this->mustache->getEscape(), $value);',
                    'return $buffer;',
                )
            ),

            array(
                '',
                array(
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME => 'name',
                    )
                ),
                'Monkey',
                false,
                'ISO-8859-1',
                array(
                    "\nclass Monkey extends Mustache_Template",
                    '$value = $this->resolveValue($context->find(\'name\'), $context, $indent);',
                    '$buffer .= $indent . htmlspecialchars($value, ENT_COMPAT, \'ISO-8859-1\');',
                    'return $buffer;',
                )
            ),

            array(
                '',
                array(
                    $this->createTextToken('foo'),
                    $this->createTextToken("\n"),
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME => 'name',
                    ),
                    array(
                        Mustache_Tokenizer::TYPE => Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::NAME => '.',
                    ),
                    $this->createTextToken("'bar'"),
                ),
                'Monkey',
                false,
                'UTF-8',
                array(
                    "\nclass Monkey extends Mustache_Template",
                    '$buffer .= $indent . \'foo\'',
                    '$buffer .= "\n"',
                    '$value = $this->resolveValue($context->find(\'name\'), $context, $indent);',
                    '$buffer .= htmlspecialchars($value, ENT_COMPAT, \'UTF-8\');',
                    '$value = $this->resolveValue($context->last(), $context, $indent);',
                    '$buffer .= \'\\\'bar\\\'\';',
                    'return $buffer;',
                )
            ),
        );
    }

    /**
     * @expectedException Mustache_Exception_SyntaxException
     */
    public function testCompilerThrowsSyntaxException()
    {
        $compiler = new Mustache_Compiler;
        $compiler->compile('', array(array(Mustache_Tokenizer::TYPE => 'invalid')), 'SomeClass');
    }

    private function createTextToken($value)
    {
        return array(
            Mustache_Tokenizer::TYPE  => Mustache_Tokenizer::T_TEXT,
            Mustache_Tokenizer::VALUE => $value,
        );
    }
}
