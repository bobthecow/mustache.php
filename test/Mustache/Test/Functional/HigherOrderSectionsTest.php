<?php
namespace Mustache\Test\Functional;

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group lambdas
 * @group functional
 */
class HigherOrderSectionsTest extends \Mustache\Test\FunctionalTestCase
{
    private $mustache;

    public function setUp()
    {
        $this->mustache = new \Mustache\Engine;
    }

    /**
     * @dataProvider sectionCallbackData
     */
    public function testSectionCallback($data, $tpl, $expect)
    {
        $this->assertEquals($expect, $this->mustache->render($tpl, $data));
    }

    public function sectionCallbackData()
    {
        $foo = new \Mustache\Test\Functional\Foo;
        $foo->doublewrap = array($foo, 'wrapWithBoth');

        $bar = new \Mustache\Test\Functional\Foo;
        $bar->trimmer = array(get_class($bar), 'staticTrim');

        return array(
            array($foo, '{{#doublewrap}}{{name}}{{/doublewrap}}', sprintf('<strong><em>%s</em></strong>', $foo->name)),
            array($bar, '{{#trimmer}}   {{name}}   {{/trimmer}}', $bar->name),
        );
    }

    public function testViewArraySectionCallback()
    {
        $tpl = $this->mustache->loadTemplate('{{#trim}}    {{name}}    {{/trim}}');

        $foo = new \Mustache\Test\Functional\Foo;

        $data = array(
            'name' => 'Bob',
            'trim' => array(get_class($foo), 'staticTrim'),
        );

        $this->assertEquals($data['name'], $tpl->render($data));
    }

    public function testMonsters()
    {
        $tpl = $this->mustache->loadTemplate('{{#title}}{{title}} {{/title}}{{name}}');

        $frank = new \Mustache\Test\Functional\Monster();
        $frank->title = 'Dr.';
        $frank->name  = 'Frankenstein';
        $this->assertEquals('Dr. Frankenstein', $tpl->render($frank));

        $dracula = new \Mustache\Test\Functional\Monster();
        $dracula->title = 'Count';
        $dracula->name  = 'Dracula';
        $this->assertEquals('Count Dracula', $tpl->render($dracula));
    }

    public function testPassthroughOptimization()
    {
        $mustache = $this->getMock('\Mustache\Engine', array('loadLambda'));
        $mustache->expects($this->never())
            ->method('loadLambda');

        $tpl = $mustache->loadTemplate('{{#wrap}}NAME{{/wrap}}');

        $foo = new \Mustache\Test\Functional\Foo;
        $foo->wrap = array($foo, 'wrapWithEm');

        $this->assertEquals('<em>NAME</em>', $tpl->render($foo));
    }

    public function testWithoutPassthroughOptimization()
    {
        $mustache = $this->getMock('\Mustache\Engine', array('loadLambda'));
        $mustache->expects($this->once())
            ->method('loadLambda')
            ->will($this->returnValue($mustache->loadTemplate('<em>{{ name }}</em>')));

        $tpl = $mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');

        $foo = new \Mustache\Test\Functional\Foo;
        $foo->wrap = array($foo, 'wrapWithEm');

        $this->assertEquals('<em>' . $foo->name . '</em>', $tpl->render($foo));
    }

    /**
     * @dataProvider cacheLambdaTemplatesData
     */
    public function testCacheLambdaTemplatesOptionWorks($dirName, $tplPrefix, $enable, $expect)
    {
        $cacheDir = $this->setUpCacheDir($dirName);
        $mustache = new \Mustache\Engine(array(
            'template_class_prefix'  => $tplPrefix,
            'cache'                  => $cacheDir,
            'cache_lambda_templates' => $enable,
        ));

        $tpl = $mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');
        $foo = new \Mustache\Test\Functional\Foo;
        $foo->wrap = array($foo, 'wrapWithEm');
        $this->assertEquals('<em>' . $foo->name . '</em>', $tpl->render($foo));
        $this->assertCount($expect, glob($cacheDir . '/*.php'));
    }

    public function cacheLambdaTemplatesData()
    {
        return array(
            array('test_enabling_lambda_cache',  '_TestEnablingLambdaCache_',  true,  2),
            array('test_disabling_lambda_cache', '_TestDisablingLambdaCache_', false, 1),
        );
    }

    protected function setUpCacheDir($name)
    {
        $cacheDir = self::$tempDir . '/' . $name;
        if (file_exists($cacheDir)) {
            self::rmdir($cacheDir);
        }
        mkdir($cacheDir, 0777, true);

        return $cacheDir;
    }
}

class Foo
{
    public $name = 'Justin';
    public $lorem = 'Lorem ipsum dolor sit amet,';

    public function wrapWithEm($text)
    {
        return sprintf('<em>%s</em>', $text);
    }

    /**
     * @param string $text
     */
    public function wrapWithStrong($text)
    {
        return sprintf('<strong>%s</strong>', $text);
    }

    public function wrapWithBoth($text)
    {
        return self::wrapWithStrong(self::wrapWithEm($text));
    }

    public static function staticTrim($text)
    {
        return trim($text);
    }
}

class Monster
{
    public $title;
    public $name;
}
