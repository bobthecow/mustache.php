<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2017 Enalean
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group lambdas
 * @group functional
 */
class Mustache_Test_Functional_StrictVariablesTest extends PHPUnit_Framework_TestCase
{
    private $mustache;

    public function setUp()
    {
        $this->mustache = new Mustache_Engine(array('strict_variables' => true));
    }

    /**
     * @@dataProvider strictVariablesProvider
     */
    public function testStrictVariables($template, $expected)
    {
        $context = array(
            'a' => array('b' => 'ab'),
            'c' => 'c',
            'd' => 'd',
        );

        $tpl = $this->mustache->loadTemplate($template);
        $this->assertEquals($expected, $tpl->render($context));
    }

    public function strictVariablesProvider()
    {
        return array(
            array('{{ c }}', 'c'),
            array('{{# a }}{{ b }}{{/ a }}', 'ab'),
            array('{{ a.b }}', 'ab'),
            array('{{# c }}{{ d }}{{/ c }}', 'd'),
            array('{{# x }}{{/ x }}', ''),
            array('{{^ x }}{{/ x }}', ''),
            array('{{# a }}{{# x }}{{/ x }}{{/ a }}', ''),
            array('{{# a.x }}{{/ a.x }}', ''),
            array('{{# d.x }}{{/ d.x }}', ''),
            array('{{^ a }}{{ x }}{{/ a }}', ''),
            array('{{# f }}{{ x }}{{/ f }}', ''),
        );
    }



    /**
     * @dataProvider unknownVariableThrowsExceptionProvider
     * @expectedException Mustache_Exception_UnknownVariableException
     */
    public function testUnknownVariableThrowsException($template)
    {
        $context = array(
            'a' => array('b' => 1),
            'c' => 1,
            'd' => 0,
        );

        $tpl = $this->mustache->loadTemplate($template);
        $tpl->render($context);
    }

    public function unknownVariableThrowsExceptionProvider()
    {
        return array(
            array('{{ e }}'),
            array('{{ .a }}'),
            array('{{ .a.b }}'),
            array('{{ a.c }}')
        );
    }
}
