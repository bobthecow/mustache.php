<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2017 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group dynamic-names
 * @group functional
 */
class Mustache_Test_Functional_DynamicPartialsTest extends PHPUnit_Framework_TestCase
{
    private $mustache;

    public function setUp()
    {
        $this->mustache = new Mustache_Engine(array(
            'pragmas' => array(Mustache_Engine::PRAGMA_DYNAMIC_NAMES),
        ));
    }

    public function getValidDynamicNamesExamples()
    {
      // technically not all dynamic names, but also not invalid
        return array(
            array('{{>* foo }}'),
            array('{{>* foo.bar.baz }}'),
            array('{{=* *=}}'),
            array('{{! *foo }}'),
            array('{{! foo.*bar }}'),
            array('{{% FILTERS }}{{! foo | *bar }}'),
            array('{{% BLOCKS }}{{< *foo }}{{/ *foo }}'),
        );
    }

    /**
     * @dataProvider getValidDynamicNamesExamples
     */
    public function testLegalInheritanceExamples($template)
    {
        $this->assertSame('', $this->mustache->render($template));
    }

    public function getDynamicNameParseErrors()
    {
        return array(
            array('{{# foo }}{{/ *foo }}'),
            array('{{^ foo }}{{/ *foo }}'),
            array('{{% BLOCKS }}{{< foo }}{{/ *foo }}'),
            array('{{% BLOCKS }}{{$ foo }}{{/ *foo }}'),
        );
    }

    /**
     * @dataProvider getDynamicNameParseErrors
     * @expectedException Mustache_Exception_SyntaxException
     * @expectedExceptionMessage Nesting error:
     */
    public function testDynamicNameParseErrors($template)
    {
        $this->mustache->render($template);
    }


    public function testDynamicBlocks()
    {
        $tpl = '{{% BLOCKS }}{{< *partial }}{{$ bar }}{{ value }}{{/ bar }}{{/ *partial }}';

        $this->mustache->setPartials(array(
            'foobarbaz' => '{{% BLOCKS }}{{$ foo }}foo{{/ foo }}{{$ bar }}bar{{/ bar }}{{$ baz }}baz{{/ baz }}',
            'qux' => 'qux',
        ));

        $result = $this->mustache->render($tpl, array(
            'partial' => 'foobarbaz',
            'value' => 'BAR',
        ));

        $this->assertSame($result, 'fooBARbaz');
    }
}
