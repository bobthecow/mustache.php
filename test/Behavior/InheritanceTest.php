<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Behavior;

use Mustache\Engine;
use Mustache\Exception\SyntaxException;
use Mustache\Test\TestCase;

class InheritanceTest extends TestCase
{
    private $mustache;

    public function set_up()
    {
        $this->mustache = new Engine();
    }

    public function getIllegalInheritanceExamples()
    {
        return [
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                ],
                [
                    'bar' => 'set by user',
                ],
                '{{< foo }}{{# bar }}{{$ baz }}{{/ baz }}{{/ bar }}{{/ foo }}',
            ],
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                ],
                [
                ],
                '{{<foo}}{{^bar}}{{$baz}}set by template{{/baz}}{{/bar}}{{/foo}}',
            ],
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                    'qux' => 'I am a partial',
                ],
                [
                ],
                '{{<foo}}{{>qux}}{{$baz}}set by template{{/baz}}{{/foo}}',
            ],
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                ],
                [],
                '{{<foo}}{{=<% %>=}}<%={{ }}=%>{{/foo}}',
            ],
        ];
    }

    public function getLegalInheritanceExamples()
    {
        return [
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                ],
                [
                    'bar' => 'set by user',
                ],
                '{{<foo}}{{bar}}{{$baz}}override{{/baz}}{{/foo}}',
                'override',
            ],
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                ],
                [
                ],
                '{{<foo}}{{! ignore me }}{{$baz}}set by template{{/baz}}{{/foo}}',
                'set by template',
            ],
            [
                [
                    'foo' => '{{$baz}}defualt content{{/baz}}',
                ],
                [],
                '{{<foo}}set by template{{$baz}}also set by template{{/baz}}{{/foo}}',
                'also set by template',
            ],
            [
                [
                    'foo' => '{{$a}}FAIL!{{/a}}',
                    'bar' => 'WIN!!',
                ],
                [],
                '{{<foo}}{{$a}}{{<bar}}FAIL{{/bar}}{{/a}}{{/foo}}',
                'WIN!!',
            ],
        ];
    }

    public function testDefaultContent()
    {
        $tpl = $this->mustache->loadTemplate('{{$title}}Default title{{/title}}');

        $data = [];

        $this->assertSame('Default title', $tpl->render($data));
    }

    public function testDefaultContentRendersVariables()
    {
        $tpl = $this->mustache->loadTemplate('{{$foo}}default {{bar}} content{{/foo}}');

        $data = [
            'bar' => 'baz',
        ];

        $this->assertSame('default baz content', $tpl->render($data));
    }

    public function testDefaultContentRendersTripleMustacheVariables()
    {
        $tpl = $this->mustache->loadTemplate('{{$foo}}default {{{bar}}} content{{/foo}}');

        $data = [
            'bar' => '<baz>',
        ];

        $this->assertSame('default <baz> content', $tpl->render($data));
    }

    public function testDefaultContentRendersSections()
    {
        $tpl = $this->mustache->loadTemplate(
            '{{$foo}}default {{#bar}}{{baz}}{{/bar}} content{{/foo}}'
        );

        $data = [
            'bar' => ['baz' => 'qux'],
        ];

        $this->assertSame('default qux content', $tpl->render($data));
    }

    public function testDefaultContentRendersNegativeSections()
    {
        $tpl = $this->mustache->loadTemplate(
            '{{$foo}}default {{^bar}}{{baz}}{{/bar}} content{{/foo}}'
        );

        $data = [
            'foo' => ['bar' => 'qux'],
            'baz' => 'three',
        ];

        $this->assertSame('default three content', $tpl->render($data));
    }

    public function testMustacheInjectionInDefaultContent()
    {
        $tpl = $this->mustache->loadTemplate(
            '{{$foo}}default {{#bar}}{{baz}}{{/bar}} content{{/foo}}'
        );

        $data = [
            'bar' => ['baz' => '{{qux}}'],
        ];

        $this->assertSame('default {{qux}} content', $tpl->render($data));
    }

    public function testDefaultContentRenderedInsideIncludedTemplates()
    {
        $partials = [
            'include' => '{{$foo}}default content{{/foo}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{/include}}'
        );

        $data = [];

        $this->assertSame('default content', $tpl->render($data));
    }

    public function testOverriddenContent()
    {
        $partials = [
            'super' => '...{{$title}}Default title{{/title}}...',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<super}}{{$title}}sub template title{{/title}}{{/super}}'
        );

        $data = [];

        $this->assertSame('...sub template title...', $tpl->render($data));
    }

    public function testOverriddenPartial()
    {
        $partials = [
            'partial' => '|{{$stuff}}...{{/stuff}}{{$default}} default{{/default}}|',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            'test {{<partial}}{{$stuff}}override1{{/stuff}}{{/partial}} {{<partial}}{{$stuff}}override2{{/stuff}}{{/partial}}'
        );

        $data = [];

        $this->assertSame('test |override1 default| |override2 default|', $tpl->render($data));
    }

    public function testBlocksDoNotLeakBetweenPartials()
    {
        $partials = [
            'partial' => '|{{$a}}A{{/a}} {{$b}}B{{/b}}|',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            'test {{<partial}}{{$a}}C{{/a}}{{/partial}} {{<partial}}{{$b}}D{{/b}}{{/partial}}'
        );

        $data = [];

        $this->assertSame('test |C B| |A D|', $tpl->render($data));
    }

    public function testDataDoesNotOverrideBlock()
    {
        $partials = [
            'include' => '{{$var}}var in include{{/var}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{$var}}var in template{{/var}}{{/include}}'
        );

        $data = [
            'var' => 'var in data',
        ];

        $this->assertSame('var in template', $tpl->render($data));
    }

    public function testDataDoesNotOverrideDefaultBlockValue()
    {
        $partials = [
            'include' => '{{$var}}var in include{{/var}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{/include}}'
        );

        $data = [
            'var' => 'var in data',
        ];

        $this->assertSame('var in include', $tpl->render($data));
    }

    public function testOverridePartialWithNewlines()
    {
        $partials = [
            'partial' => '{{$ballmer}}peaking{{/ballmer}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            "{{<partial}}{{\$ballmer}}\npeaked\n\n:(\n{{/ballmer}}{{/partial}}"
        );

        $data = [];

        $this->assertSame("peaked\n\n:(\n", $tpl->render($data));
    }

    public function testInheritIndentationWhenOverridingAPartial()
    {
        $partials = [
            'partial' => 'stop:
                    {{$nineties}}collaborate and listen{{/nineties}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<partial}}{{$nineties}}hammer time{{/nineties}}{{/partial}}'
        );

        $data = [];

        $this->assertSame(
            'stop:
                    hammer time',
            $tpl->render($data)
        );
    }

    public function testInheritSpacingWhenOverridingAPartial()
    {
        $partials = [
            'parent' => 'collaborate_and{{$id}}{{/id}}',
            'child'  => '{{<parent}}{{$id}}_listen{{/id}}{{/parent}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            'stop:
              {{>child}}'
        );

        $data = [];

        $this->assertSame(
            'stop:
              collaborate_and_listen',
            $tpl->render($data)
        );
    }

    public function testInlineParentKeepsTrailingNewline()
    {
        $partials = [
            'parent' => '{{$block}}{{/block}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{$block}}x{{/block}}{{/parent}}
Y'
        );

        $data = [];

        $this->assertSame(
            'x
Y',
            $tpl->render($data)
        );
    }

    public function testStandaloneBlockDedentsIndentedTagLines()
    {
        $partials = [
            'parent' => 'Hi,
  {{$block}}{{/block}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{$block}}
  {{name}}
{{/block}}{{/parent}}'
        );

        $data = ['name' => 'Nineties'];

        $this->assertSame(
            'Hi,
  Nineties
',
            $tpl->render($data)
        );
    }

    public function testStandaloneBlockDedentPreservesSpacingAfterTags()
    {
        $partials = [
            'parent' => 'Hi,
  {{$block}}{{/block}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{$block}}
  {{name}} suffix
{{/block}}{{/parent}}'
        );

        $data = ['name' => 'Nineties'];

        $this->assertSame(
            'Hi,
  Nineties suffix
',
            $tpl->render($data)
        );
    }

    public function testOverrideOneSubstitutionButNotTheOther()
    {
        $partials = [
            'partial' => '{{$stuff}}default one{{/stuff}}, {{$stuff2}}default two{{/stuff2}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<partial}}{{$stuff2}}override two{{/stuff2}}{{/partial}}'
        );

        $data = [];

        $this->assertSame('default one, override two', $tpl->render($data));
    }

    public function testSuperTemplatesWithNoParameters()
    {
        $partials = [
            'include' => '{{$foo}}default content{{/foo}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{>include}}|{{<include}}{{/include}}'
        );

        $data = [];

        $this->assertSame('default content|default content', $tpl->render($data));
    }

    public function testRecursionInInheritedTemplates()
    {
        $partials = [
            'include'  => '{{$foo}}default content{{/foo}} {{$bar}}{{<include2}}{{/include2}}{{/bar}}',
            'include2' => '{{$foo}}include2 default content{{/foo}} {{<include}}{{$bar}}don\'t recurse{{/bar}}{{/include}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{$foo}}override{{/foo}}{{/include}}'
        );

        $data = [];

        $this->assertSame('override override override don\'t recurse', $tpl->render($data));
    }

    public function testNestedParentBlocksWithSameNameUseNestedOverride()
    {
        $partials = [
            'base'  => 'base({{$content}}base default{{/content}})',
            'block' => 'block({{$content}}block default{{/content}})',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<base}}{{$content}}outer {{<block}}{{$content}}inner{{/content}}{{/block}}{{/content}}{{/base}}'
        );

        $this->assertSame('base(outer block(inner))', $tpl->render([]));
    }

    public function testNestedParentBlocksWithSameNameFallBackToNestedDefault()
    {
        $partials = [
            'base'  => 'base({{$content}}base default{{/content}})',
            'block' => 'block({{$content}}block default{{/content}})',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<base}}{{$content}}outer {{<block}}{{/block}}{{/content}}{{/base}}'
        );

        $this->assertSame('base(outer block(block default))', $tpl->render([]));
    }

    public function testNestedParentBlocksDoNotInheritOuterBlockContext()
    {
        $partials = [
            'base'  => 'base({{$title}}base title{{/title}}|{{$content}}base default{{/content}})',
            'block' => 'block({{$title}}block title{{/title}})',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<base}}{{$title}}page title{{/title}}{{$content}}{{<block}}{{/block}}{{/content}}{{/base}}'
        );

        $this->assertSame('base(page title|block(block title))', $tpl->render([]));
    }

    public function testTopLevelSubstitutionsTakePrecedenceOverNestedDefaults()
    {
        $partials = [
            'base' => '<html><head><title>{{$title}}My Site{{/title}}</title></head></html>',
            'page' => '{{<base}}{{$title}}{{page.title}} | My Site{{/title}}{{/base}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<page}}{{$title}}{{article.title}} | My Site{{/title}}{{/page}}'
        );

        $data = [
            'article' => [
                'title' => 'Article Title!',
            ],
            'page' => [
                'title' => 'Page Title!',
            ],
        ];

        $this->assertSame('<html><head><title>Article Title! | My Site</title></head></html>', $tpl->render($data));
    }

    public function testTopLevelSubstitutionsTakePrecedenceInMultilevelInheritance()
    {
        $partials = [
            'parent'      => '{{<older}}{{$a}}p{{/a}}{{/older}}',
            'older'       => '{{<grandParent}}{{$a}}o{{/a}}{{/grandParent}}',
            'grandParent' => '{{$a}}g{{/a}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{$a}}c{{/a}}{{/parent}}'
        );

        $data = [];

        $this->assertSame('c', $tpl->render($data));
    }

    public function testMultiLevelInheritanceNoSubChild()
    {
        $partials = [
            'parent'      => '{{<older}}{{$a}}p{{/a}}{{/older}}',
            'older'       => '{{<grandParent}}{{$a}}o{{/a}}{{/grandParent}}',
            'grandParent' => '{{$a}}g{{/a}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{/parent}}'
        );

        $data = [];

        $this->assertSame('p', $tpl->render($data));
    }

    public function testIgnoreTextInsideSuperTemplatesButParseArgs()
    {
        $partials = [
            'include' => '{{$foo}}default content{{/foo}}',
         ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}} asdfasd {{$foo}}hmm{{/foo}} asdfasdfasdf {{/include}}'
        );

        $data = [];

        $this->assertSame('hmm', $tpl->render($data));
    }

    public function testIgnoreTextInsideSuperTemplates()
    {
        $partials = [
            'include' => '{{$foo}}default content{{/foo}}',
         ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}} asdfasd asdfasdfasdf {{/include}}'
        );

        $data = [];

        $this->assertSame('default content', $tpl->render($data));
    }

    public function testInheritanceWithLazyEvaluation()
    {
        $partials = [
            'parent' => '{{#items}}{{$value}}ignored{{/value}}{{/items}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{$value}}<{{ . }}>{{/value}}{{/parent}}'
        );

        $data = ['items' => [1, 2, 3]];

        $this->assertSame('<1><2><3>', $tpl->render($data));
    }

    public function testInheritanceWithLazyEvaluationWhitespaceIgnored()
    {
        $partials = [
            'parent' => '{{#items}}{{$value}}\n\nignored\n\n{{/value}}{{/items}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}\n\n\n{{$value}}<{{ . }}>{{/value}}\n\n{{/parent}}'
        );

        $data = ['items' => [1, 2, 3]];

        $this->assertSame('<1><2><3>', $tpl->render($data));
    }

    public function testInheritanceWithLazyEvaluationAndSections()
    {
        $partials = [
            'parent' => '{{#items}}{{$value}}\n\nignored {{.}} {{#more}} there is more {{/more}}\n\n{{/value}}{{/items}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}\n\n\n{{$value}}<{{ . }}>{{#more}} there is less {{/more}}{{/value}}\n\n{{/parent}}'
        );

        $data = ['items' => [1, 2, 3], 'more' => 'stuff'];

        $this->assertSame('<1> there is less <2> there is less <3> there is less ', $tpl->render($data));
    }

    /**
     * @dataProvider getIllegalInheritanceExamples
     */
    public function testIllegalInheritanceExamples(array $partials, array $data, $template)
    {
        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Illegal content in < parent tag');

        $this->mustache->setPartials($partials);
        $tpl = $this->mustache->loadTemplate($template);
        $tpl->render($data);
    }

    /**
     * @dataProvider getLegalInheritanceExamples
     */
    public function testLegalInheritanceExamples(array $partials, array $data, $template, $expect)
    {
        $this->mustache->setPartials($partials);
        $tpl = $this->mustache->loadTemplate($template);
        $this->assertSame($expect, $tpl->render($data));
    }

    public function testPartialInsideBlockOverrideNestedInSection()
    {
        $partials = [
            'parent' => '{{$content}}default{{/content}}',
            'row'    => '{{name}} ',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{#items}}{{<parent}}{{$content}}{{>row}}{{/content}}{{/parent}}{{/items}}'
        );

        $data = ['items' => [['name' => 'a'], ['name' => 'b']]];

        $this->assertSame('a b ', $tpl->render($data));
    }

    public function testDisabledInheritance()
    {
        // With inheritance disabled, the block tag (`{{$bar}}`) will be treated as a regular
        // variable tag, so the `{{/bar}}` tag will be parsed as a mismatched closing tag for
        // `{{< foo }}`.

        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Nesting error: foo (on line 0) vs. bar (on line 0)');

        $mustache = new Engine([
            'inheritance' => false,
        ]);

        $mustache->setPartials([
            'foo' => '{{$bar}}baz{{/bar}}',
        ]);

        $tpl = $mustache->loadTemplate('{{< foo }}{{$bar}}qux{{/bar}}{{/foo}}');
        $tpl->render([
            'foo' => 'foo content',
            'bar' => 'bar content',
        ]);
    }
}
