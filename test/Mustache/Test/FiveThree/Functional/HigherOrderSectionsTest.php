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
 * @group lambdas
 * @group functional
 */
class Mustache_Test_FiveThree_Functional_HigherOrderSectionsTest extends PHPUnit_Framework_TestCase
{
    private $mustache;

    public function setUp()
    {
        $this->mustache = new Mustache_Engine();
    }

    public function testAnonymousFunctionSectionCallback()
    {
        $tpl = $this->mustache->loadTemplate('{{#wrapper}}{{name}}{{/wrapper}}');

        $foo = new Mustache_Test_FiveThree_Functional_Foo();
        $foo->name = 'Mario';
        $foo->wrapper = function ($text) {
            return sprintf('<div class="anonymous">%s</div>', $text);
        };

        $this->assertEquals(sprintf('<div class="anonymous">%s</div>', $foo->name), $tpl->render($foo));
    }

    public function testSectionCallback()
    {
        $one = $this->mustache->loadTemplate('{{name}}');
        $two = $this->mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');

        $foo = new Mustache_Test_FiveThree_Functional_Foo();
        $foo->name = 'Luigi';

        $this->assertEquals($foo->name, $one->render($foo));
        $this->assertEquals(sprintf('<em>%s</em>', $foo->name), $two->render($foo));
    }

    public function testViewArrayAnonymousSectionCallback()
    {
        $tpl = $this->mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');

        $data = array(
            'name' => 'Bob',
            'wrap' => function ($text) {
                return sprintf('[[%s]]', $text);
            },
        );

        $this->assertEquals(sprintf('[[%s]]', $data['name']), $tpl->render($data));
    }
}

class Mustache_Test_FiveThree_Functional_Foo
{
    public $name  = 'Justin';
    public $lorem = 'Lorem ipsum dolor sit amet,';
    public $wrap;

    public function __construct()
    {
        $this->wrap = function ($text) {
            return sprintf('<em>%s</em>', $text);
        };
    }
}
