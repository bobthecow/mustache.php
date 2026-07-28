<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test;

use Mustache\CompileOptions;
use Mustache\Compiler;
use Mustache\Engine;
use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\SyntaxException;
use Mustache\Parser;
use Mustache\Tokenizer;

class CompilerTest extends TestCase
{
    /**
     * @dataProvider getCompileValues
     */
    public function testCompile($source, array $tree, $name, $customEscaper, $entityFlags, $charset, array $expected)
    {
        $compiler = new Compiler();

        $compiled = $compiler->compile($source, $tree, $name, $customEscaper, $charset, false, $entityFlags);
        foreach ($expected as $contains) {
            $this->assertStringContainsString($contains, $compiled);
        }
    }

    public function getCompileValues()
    {
        return [
            ['', [], 'Banana', false, ENT_COMPAT, 'ISO-8859-1', [
                "\nclass Banana extends \Mustache\Template",
                'return $buffer;',
            ]],

            ['', [$this->createTextToken('TEXT')], 'Monkey', false, ENT_COMPAT, 'UTF-8', [
                "\nclass Monkey extends \Mustache\Template",
                '$buffer .= $indent . \'TEXT\';',
                'return $buffer;',
            ]],

            [
                '',
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                ],
                'Monkey',
                true,
                ENT_COMPAT,
                'ISO-8859-1',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= $indent . ($value === null ? \'\' : $this->stringifyValue(call_user_func($this->mustache->getEscape(), $value)));',
                    'return $buffer;',
                ],
            ],

            [
                '',
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                ],
                'Monkey',
                false,
                ENT_COMPAT,
                'ISO-8859-1',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= $indent . ($value === null ? \'\' : htmlspecialchars((is_scalar($value) ? $value : $this->stringifyNonScalar($value)), ' . ENT_COMPAT . ', \'ISO-8859-1\'));',
                    'return $buffer;',
                ],
            ],

            [
                '',
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                ],
                'Monkey',
                false,
                ENT_QUOTES,
                'ISO-8859-1',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= $indent . ($value === null ? \'\' : htmlspecialchars((is_scalar($value) ? $value : $this->stringifyNonScalar($value)), ' . ENT_QUOTES . ', \'ISO-8859-1\'));',
                    'return $buffer;',
                ],
            ],

            [
                '',
                [
                    $this->createTextToken("foo\n"),
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => '.',
                    ],
                    $this->createTextToken("'bar'"),
                ],
                'Monkey',
                false,
                ENT_COMPAT,
                'UTF-8',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    "\$buffer .= \$indent . 'foo\n';",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= ($value === null ? \'\' : htmlspecialchars((is_scalar($value) ? $value : $this->stringifyNonScalar($value)), ' . ENT_COMPAT . ', \'UTF-8\'));',
                    '$value = $this->resolveValue($context->last(), $context);',
                    '$buffer .= \'\\\'bar\\\'\';',
                    'return $buffer;',
                ],
            ],
        ];
    }

    public function testCompilerThrowsSyntaxException()
    {
        $this->expectException(SyntaxException::class);
        $compiler = new Compiler();
        $compiler->compile('', [[Tokenizer::TYPE => 'invalid']], 'SomeClass');
    }

    public function testCompilerRejectsInvalidClassName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Compiler class name must be a valid PHP class name');

        $compiler = new Compiler();
        $compiler->compile('', [], 'SomeClass {} class Injected');
    }

    public function testStaticPartialsInSectionsAreLazyLoadedInsideLoop()
    {
        $compiled = $this->compileSource('{{# items }}{{> row }}{{/ items }}');

        $load = strpos($compiled, '$this->mustache->loadPartial(\'row\')');
        $loop = strpos($compiled, 'foreach ($values as $value)');

        $this->assertNotFalse($load);
        $this->assertNotFalse($loop);
        $this->assertGreaterThan($loop, $load);
    }

    public function testDynamicPartialsInSectionsAreNotCached()
    {
        $compiled = $this->compileSource('{{# items }}{{> *partial }}{{/ items }}');

        $load = strpos($compiled, '$this->mustache->loadPartial($this->resolveValue($context->find(\'partial\'), $context))');
        $loop = strpos($compiled, 'foreach ($values as $value)');

        $this->assertNotFalse($load);
        $this->assertNotFalse($loop);
        $this->assertGreaterThan($loop, $load);
    }

    public function testParentsWithoutIndentPassRuntimeIndent()
    {
        $compiled = $this->compileSource('{{< layout }}{{/ layout }}');

        $this->assertStringContainsString('$parent->renderInternal($context, $indent);', $compiled);
        $this->assertStringNotContainsString('$indent . \'\'', $compiled);
    }

    public function testParentsWithIndentPassIndentArgument()
    {
        $compiled = $this->compileSource("  {{< layout }}\n  {{/ layout }}\n");

        $this->assertStringContainsString('$parent->renderInternal($context, $indent . \'  \');', $compiled);
    }

    public function testStaticParentsInSectionsAreLazyLoadedInsideLoop()
    {
        $compiled = $this->compileSource('{{# items }}{{< layout }}{{$ body }}{{ name }}{{/ body }}{{/ layout }}{{/ items }}');

        $load = strpos($compiled, '$this->mustache->loadPartial(\'layout\')');
        $loop = strpos($compiled, 'foreach ($values as $value)');

        $this->assertNotFalse($load);
        $this->assertNotFalse($loop);
        $this->assertGreaterThan($loop, $load);
    }

    public function testSinglePlainLookupDoesNotUseContextFrameFastPath()
    {
        $compiled = $this->compileSource('{{ name }}');

        $this->assertStringContainsString('$value = $this->resolveValue($context->find(\'name\'), $context);', $compiled);
        $this->assertFalse(strpos($compiled, '$frame = $context->last();'));
    }

    public function testRepeatedPlainLookupsUseContextFrameFastPath()
    {
        $compiled = $this->compileSource('{{ name }}{{ label }}');

        $hoist = strpos($compiled, '$frame = $context->last();');
        $name = strpos($compiled, "array_key_exists('name', \$frame)");
        $label = strpos($compiled, "array_key_exists('label', \$frame)");

        $this->assertNotFalse($hoist);
        $this->assertNotFalse($name);
        $this->assertNotFalse($label);
        $this->assertLessThan($name, $hoist);
        $this->assertLessThan($label, $hoist);
    }

    public function testDottedAndCurrentContextLookupsDoNotUseContextFrameFastPath()
    {
        $compiled = $this->compileSource('{{ person.name }}{{ . }}');

        $this->assertStringContainsString('$value = $this->resolveValue($context->findDot(\'person.name\'), $context);', $compiled);
        $this->assertStringContainsString('$value = $this->resolveValue($context->last(), $context);', $compiled);
        $this->assertFalse(strpos($compiled, '$frame = $context->last();'));
    }

    public function testLambdasDisabledCompileDirectVariableLookups()
    {
        $compiled = $this->compileSource('{{ name }}{{ person.name }}{{ . }}', Engine::STRICT_NONE, false, false);

        $this->assertStringContainsString('$value = $context->find(\'name\');', $compiled);
        $this->assertStringContainsString('$value = $context->findDot(\'person.name\');', $compiled);
        $this->assertStringContainsString('$value = $context->last();', $compiled);
        $this->assertStringNotContainsString('$this->resolveValue(', $compiled);
    }

    public function testLambdasDisabledCompileDirectDynamicNameLookups()
    {
        $compiled = $this->compileSource(
            '{{> *partial }}{{< *parent }}{{/ *parent }}',
            Engine::STRICT_NONE,
            false,
            false
        );

        $this->assertStringContainsString('$this->mustache->loadPartial($context->find(\'partial\'))', $compiled);
        $this->assertStringContainsString('$this->mustache->loadPartial($context->find(\'parent\'))', $compiled);
        $this->assertStringNotContainsString('$this->resolveValue(', $compiled);
    }

    public function testSectionBodyContextFrameFastPathIsHoistedAfterPush()
    {
        $compiled = $this->compileSource('{{# items }}{{ name }}{{ label }}{{/ items }}');

        $section = strpos($compiled, '$value = $context->find(\'items\');');
        $push = strpos($compiled, '$context->push($value);');
        $hoist = strpos($compiled, '$frame = $context->last();');
        $name = strpos($compiled, "array_key_exists('name', \$frame)");

        $this->assertNotFalse($section);
        $this->assertNotFalse($push);
        $this->assertNotFalse($hoist);
        $this->assertNotFalse($name);
        $this->assertLessThan($hoist, $push);
        $this->assertLessThan($name, $hoist);
    }

    public function testSectionLambdaPassthroughUsesActiveOpeningDelimiter()
    {
        $compiled = $this->compileSource('{{=<% %>=}}<%#wrap%><%name%><%/wrap%>');

        $this->assertStringContainsString("strpos(\$value, '<%')", $compiled);
    }

    public function testSectionLambdaSourceUsesTemplateSourceOffsets()
    {
        $source = '{{#wrap}}{{name}}{{/wrap}}';
        $compiled = $this->compileSource($source);

        $this->assertStringContainsString('private static $source = ' . var_export($source, true) . ';', $compiled);
        $this->assertStringContainsString('$source = substr(self::$source, 9, 8);', $compiled);
        $this->assertStringNotContainsString('$source = \'{{name}}\';', $compiled);
    }

    public function testNestedSectionLambdaSourceIsStoredOnce()
    {
        $source = '{{#a}}{{#b}}x{{/b}}{{/a}}';
        $compiled = $this->compileSource($source);

        $this->assertSame(1, substr_count($compiled, var_export($source, true)));
        $this->assertStringContainsString('$source = substr(self::$source, 6, 13);', $compiled);
        $this->assertStringContainsString('$source = substr(self::$source, 12, 1);', $compiled);
    }

    public function testContextFrameFastPathPreservesNullArrayKeys()
    {
        $mustache = new Engine();
        $template = '{{# items }}{{ name }}{{ label }}{{/ items }}';
        $data = [
            'name' => 'parent',
            'items' => [
                [
                    'name' => null,
                    'label' => 'child',
                ],
            ],
        ];

        $this->assertSame('child', $mustache->render($template, $data));
    }

    public function testStrictTagsCompileTemplateFlag()
    {
        $compiled = $this->compileSource('{{ name }}', Engine::STRICT_INTERPOLATION);

        $this->assertStringContainsString('protected $strictTags = 1;', $compiled);
    }

    public function testStrictSectionsCompileLookupFlag()
    {
        $compiled = $this->compileSource('{{# missing }}{{ name }}{{/ missing }}', Engine::STRICT_SECTIONS);

        $this->assertStringNotContainsString('catch (\\Mustache\\Exception\\UnknownVariableException $e)', $compiled);
        $this->assertStringContainsString('$value = $context->find(\'missing\', 2);', $compiled);
        $this->assertStringContainsString('$buffer .= $this->section', $compiled);
    }

    public function testStrictPartialsCompileStrictLoader()
    {
        $compiled = $this->compileSource('{{> missing }}', Engine::STRICT_PARTIALS);

        $this->assertStringContainsString('$this->mustache->loadPartial(\'missing\', true)', $compiled);
    }

    public function testStrictParentsCompileStrictLoader()
    {
        $compiled = $this->compileSource('{{< layout }}{{/ layout }}', Engine::STRICT_PARENTS);

        $this->assertStringContainsString('$this->mustache->loadPartial(\'layout\', true)', $compiled);
    }

    public function testDebugRenderingCompileTemplateFrames()
    {
        $compiled = $this->compileSource('{{# items }}{{ name }}{{/ items }}', Engine::STRICT_NONE, true);

        $this->assertStringContainsString('$context->pushRenderingFrame(array(', $compiled);
        $this->assertStringContainsString('\'type\' => \'section\'', $compiled);
        $this->assertStringContainsString('\'type\' => \'variable\'', $compiled);
        $this->assertStringContainsString('$context->popRenderingFrame();', $compiled);
    }

    public function testDefaultCompileTemplateDoesNotIncludeDebugFrames()
    {
        $compiled = $this->compileSource('{{ name }}');

        $this->assertStringNotContainsString('pushRenderingFrame', $compiled);
        $this->assertStringNotContainsString('popRenderingFrame', $compiled);
    }

    public function testDebugRenderingDoesNotInstrumentBlockArgumentsInsideArrays()
    {
        $compiled = $this->compileSource('{{< layout }}{{$ title }}{{ name }}{{/ title }}{{/ layout }}', Engine::STRICT_NONE, true);

        $this->assertStringContainsString('$context->pushBlockContext([', $compiled);
        $this->assertStringContainsString("'title' => [\$this, 'block", $compiled);
        $this->assertStringNotContainsString("\$context->pushBlockContext([\n            \$context->pushRenderingFrame", $compiled);
    }

    /**
     * @param string $value
     */
    private function createTextToken($value)
    {
        return [
            Tokenizer::TYPE => Tokenizer::T_TEXT,
            Tokenizer::VALUE => $value,
        ];
    }

    /**
     * @param string $source
     *
     * @return string
     */
    private function compileSource($source, $strictTags = Engine::STRICT_NONE, $debugRendering = false, $lambdas = true)
    {
        $compiler = new Compiler();
        $compiler->setOptions(['lambdas' => $lambdas]);

        $tokens = (new Tokenizer())->scan($source);
        $tree = (new Parser())->parse($tokens);

        return $compiler->compile($source, $tree, 'TestTemplate', new CompileOptions([
            'debug_rendering'  => $debugRendering,
            'strict_tags'      => $strictTags,
        ]));
    }
}
