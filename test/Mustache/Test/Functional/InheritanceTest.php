<?php

/**
 * @group inheritance
 * @group functional
 */
class Mustache_Test_Functional_InheritanceTest extends PHPUnit_Framework_TestCase
{
    private $mustache;

    public function setUp()
    {
        $this->mustache = new Mustache_Engine(array(
            'pragmas' => array(Mustache_Engine::PRAGMA_BLOCKS),
        ));
    }

    public function getIllegalInheritanceExamples()
    {
        return array(
            array(
                array(
                    'foo' => '{{$baz}}default content{{/baz}}',
                ),
                array(
                    'bar' => 'set by user',
                ),
                '{{< foo }}{{# bar }}{{$ baz }}{{/ baz }}{{/ bar }}{{/ foo }}',
            ),
            array(
                array(
                    'foo' => '{{$baz}}default content{{/baz}}',
                ),
                array(
                ),
                '{{<foo}}{{^bar}}{{$baz}}set by template{{/baz}}{{/bar}}{{/foo}}',
            ),
            array(
                array(
                    'foo' => '{{$baz}}default content{{/baz}}',
                    'qux' => 'I am a partial',
                ),
                array(
                ),
                '{{<foo}}{{>qux}}{{$baz}}set by template{{/baz}}{{/foo}}',
            ),
            array(
                array(
                    'foo' => '{{$baz}}default content{{/baz}}',
                ),
                array(),
                '{{<foo}}{{=<% %>=}}<%={{ }}=%>{{/foo}}',
            ),
        );
    }

    public function getLegalInheritanceExamples()
    {
        return array(
            array(
                array(
                    'foo' => '{{$baz}}default content{{/baz}}',
                ),
                array(
                    'bar' => 'set by user',
                ),
                '{{<foo}}{{bar}}{{$baz}}override{{/baz}}{{/foo}}',
                'override',
            ),
            array(
                array(
                    'foo' => '{{$baz}}default content{{/baz}}',
                ),
                array(
                ),
                '{{<foo}}{{! ignore me }}{{$baz}}set by template{{/baz}}{{/foo}}',
                'set by template',
            ),
            array(
                array(
                    'foo' => '{{$baz}}defualt content{{/baz}}',
                ),
                array(),
                '{{<foo}}set by template{{$baz}}also set by template{{/baz}}{{/foo}}',
                'also set by template',
            ),
        );
    }

    public function testDefaultContent()
    {
        $tpl = $this->mustache->loadTemplate('{{$title}}Default title{{/title}}');

        $data = array();

        $this->assertEquals('Default title', $tpl->render($data));
    }

    public function testDefaultContentRendersVariables()
    {
        $tpl = $this->mustache->loadTemplate('{{$foo}}default {{bar}} content{{/foo}}');

        $data = array(
            'bar' => 'baz',
        );

        $this->assertEquals('default baz content', $tpl->render($data));
    }

    public function testDefaultContentRendersTripleMustacheVariables()
    {
        $tpl = $this->mustache->loadTemplate('{{$foo}}default {{{bar}}} content{{/foo}}');

        $data = array(
            'bar' => '<baz>',
        );

        $this->assertEquals('default <baz> content', $tpl->render($data));
    }

    public function testDefaultContentRendersSections()
    {
        $tpl = $this->mustache->loadTemplate(
            '{{$foo}}default {{#bar}}{{baz}}{{/bar}} content{{/foo}}'
        );

        $data = array(
            'bar' => array('baz' => 'qux'),
        );

        $this->assertEquals('default qux content', $tpl->render($data));
    }

    public function testDefaultContentRendersNegativeSections()
    {
        $tpl = $this->mustache->loadTemplate(
            '{{$foo}}default {{^bar}}{{baz}}{{/bar}} content{{/foo}}'
        );

        $data = array(
            'foo' => array('bar' => 'qux'),
            'baz' => 'three',
        );

        $this->assertEquals('default three content', $tpl->render($data));
    }

    public function testMustacheInjectionInDefaultContent()
    {
        $tpl = $this->mustache->loadTemplate(
            '{{$foo}}default {{#bar}}{{baz}}{{/bar}} content{{/foo}}'
        );

        $data = array(
            'bar' => array('baz' => '{{qux}}'),
        );

        $this->assertEquals('default {{qux}} content', $tpl->render($data));
    }

    public function testDefaultContentRenderedInsideIncludedTemplates()
    {
        $partials = array(
            'include' => '{{$foo}}default content{{/foo}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{/include}}'
        );

        $data = array();

        $this->assertEquals('default content', $tpl->render($data));
    }

    public function testOverriddenContent()
    {
        $partials = array(
            'super' => '...{{$title}}Default title{{/title}}...',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<super}}{{$title}}sub template title{{/title}}{{/super}}'
        );

        $data = array();

        $this->assertEquals('...sub template title...', $tpl->render($data));
    }

    public function testOverriddenPartial()
    {
        $partials = array(
            'partial' => '|{{$stuff}}...{{/stuff}}{{$default}} default{{/default}}|',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            'test {{<partial}}{{$stuff}}override1{{/stuff}}{{/partial}} {{<partial}}{{$stuff}}override2{{/stuff}}{{/partial}}'
        );

        $data = array();

        $this->assertEquals('test |override1 default| |override2 default|', $tpl->render($data));
    }

    public function testDataDoesNotOverrideBlock()
    {
        $partials = array(
            'include' => '{{$var}}var in include{{/var}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{$var}}var in template{{/var}}{{/include}}'
        );

        $data = array(
            'var' => 'var in data',
        );

        $this->assertEquals('var in template', $tpl->render($data));
    }

    public function testDataDoesNotOverrideDefaultBlockValue()
    {
        $partials = array(
            'include' => '{{$var}}var in include{{/var}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{/include}}'
        );

        $data = array(
            'var' => 'var in data',
        );

        $this->assertEquals('var in include', $tpl->render($data));
    }

    public function testOverridePartialWithNewlines()
    {
        $partials = array(
            'partial' => '{{$ballmer}}peaking{{/ballmer}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            "{{<partial}}{{\$ballmer}}\npeaked\n\n:(\n{{/ballmer}}{{/partial}}"
        );

        $data = array();

        $this->assertEquals("peaked\n\n:(\n", $tpl->render($data));
    }

    public function testInheritIndentationWhenOverridingAPartial()
    {
        $partials = array(
            'partial' => 'stop:
                    {{$nineties}}collaborate and listen{{/nineties}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<partial}}{{$nineties}}hammer time{{/nineties}}{{/partial}}'
        );

        $data = array();

        $this->assertEquals(
            'stop:
                    hammer time',
            $tpl->render($data)
        );
    }

    public function testInheritSpacingWhenOverridingAPartial()
    {
        $partials = array(
            'parent' => 'collaborate_and{{$id}}{{/id}}',
            'child'  => '{{<parent}}{{$id}}_listen{{/id}}{{/parent}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            'stop:
              {{>child}}'
        );

        $data = array();

        $this->assertEquals(
            'stop:
              collaborate_and_listen',
            $tpl->render($data)
        );
    }

    public function testOverrideOneSubstitutionButNotTheOther()
    {
        $partials = array(
            'partial' => '{{$stuff}}default one{{/stuff}}, {{$stuff2}}default two{{/stuff2}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<partial}}{{$stuff2}}override two{{/stuff2}}{{/partial}}'
        );

        $data = array();

        $this->assertEquals('default one, override two', $tpl->render($data));
    }

    public function testSuperTemplatesWithNoParameters()
    {
        $partials = array(
            'include' => '{{$foo}}default content{{/foo}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{>include}}|{{<include}}{{/include}}'
        );

        $data = array();

        $this->assertEquals('default content|default content', $tpl->render($data));
    }

    public function testRecursionInInheritedTemplates()
    {
        $partials = array(
            'include'  => '{{$foo}}default content{{/foo}} {{$bar}}{{<include2}}{{/include2}}{{/bar}}',
            'include2' => '{{$foo}}include2 default content{{/foo}} {{<include}}{{$bar}}don\'t recurse{{/bar}}{{/include}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{$foo}}override{{/foo}}{{/include}}'
        );

        $data = array();

        $this->assertEquals('override override override don\'t recurse', $tpl->render($data));
    }

    public function testTopLevelSubstitutionsTakePrecedenceInMultilevelInheritance()
    {
        $partials = array(
            'parent'      => '{{<older}}{{$a}}p{{/a}}{{/older}}',
            'older'       => '{{<grandParent}}{{$a}}o{{/a}}{{/grandParent}}',
            'grandParent' => '{{$a}}g{{/a}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{$a}}c{{/a}}{{/parent}}'
        );

        $data = array();

        $this->assertEquals('c', $tpl->render($data));
    }

    public function testMultiLevelInheritanceNoSubChild()
    {
        $partials = array(
            'parent'      => '{{<older}}{{$a}}p{{/a}}{{/older}}',
            'older'       => '{{<grandParent}}{{$a}}o{{/a}}{{/grandParent}}',
            'grandParent' => '{{$a}}g{{/a}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{/parent}}'
        );

        $data = array();

        $this->assertEquals('p', $tpl->render($data));
    }

    public function testIgnoreTextInsideSuperTemplatesButParseArgs()
    {
        $partials = array(
            'include' => '{{$foo}}default content{{/foo}}',
         );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}} asdfasd {{$foo}}hmm{{/foo}} asdfasdfasdf {{/include}}'
        );

        $data = array();

        $this->assertEquals('hmm', $tpl->render($data));
    }

    public function testIgnoreTextInsideSuperTemplates()
    {
        $partials = array(
            'include' => '{{$foo}}default content{{/foo}}',
         );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}} asdfasd asdfasdfasdf {{/include}}'
        );

        $data = array();

        $this->assertEquals('default content', $tpl->render($data));
    }

    public function testInheritanceWithLazyEvaluation()
    {
        $partials = array(
            'parent' => '{{#items}}{{$value}}ignored{{/value}}{{/items}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{$value}}<{{ . }}>{{/value}}{{/parent}}'
        );

        $data = array('items' => array(1, 2, 3));

        $this->assertEquals('<1><2><3>', $tpl->render($data));
    }

    public function testInheritanceWithLazyEvaluationWhitespaceIgnored()
    {
        $partials = array(
            'parent' => '{{#items}}{{$value}}\n\nignored\n\n{{/value}}{{/items}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}\n\n\n{{$value}}<{{ . }}>{{/value}}\n\n{{/parent}}'
        );

        $data = array('items' => array(1, 2, 3));

        $this->assertEquals('<1><2><3>', $tpl->render($data));
    }

    public function testInheritanceWithLazyEvaluationAndSections()
    {
        $partials = array(
            'parent' => '{{#items}}{{$value}}\n\nignored {{.}} {{#more}} there is more {{/more}}\n\n{{/value}}{{/items}}',
        );

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}\n\n\n{{$value}}<{{ . }}>{{#more}} there is less {{/more}}{{/value}}\n\n{{/parent}}'
        );

        $data = array('items' => array(1, 2, 3), 'more' => 'stuff');

        $this->assertEquals('<1> there is less <2> there is less <3> there is less ', $tpl->render($data));
    }

    /**
     * @dataProvider getIllegalInheritanceExamples
     * @expectedException Mustache_Exception_SyntaxException
     * @expectedExceptionMessage Illegal content in < parent tag
     */
    public function testIllegalInheritanceExamples($partials, $data, $template)
    {
        $this->mustache->setPartials($partials);
        $tpl = $this->mustache->loadTemplate($template);
        $tpl->render($data);
    }

    /**
     * @dataProvider getLegalInheritanceExamples
     */
    public function testLegalInheritanceExamples($partials, $data, $template, $expect)
    {
        $this->mustache->setPartials($partials);
        $tpl = $this->mustache->loadTemplate($template);
        $this->assertSame($expect, $tpl->render($data));
    }
}
