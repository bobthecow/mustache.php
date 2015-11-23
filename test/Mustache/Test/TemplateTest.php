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
class Mustache_Test_TemplateTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $mustache = new Mustache_Engine();
        $template = new Mustache_Test_TemplateStub($mustache);
        $this->assertSame($mustache, $template->getMustache());
    }

    public function testRendering()
    {
        $rendered = '<< wheee >>';
        $global_variables = array('globalKey' => 'globalValue');
        $mustache = new Mustache_Engine(/*$options =*/array('global_variables' => $global_variables));
        $template = new Mustache_Test_TemplateStub($mustache);
        $template->rendered = $rendered;
        $context  = new Mustache_Context(/*$context =*/$global_variables);

        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $this->assertEquals($rendered, $template());
        }

        $this->assertEquals($rendered, $template->render());
        $this->assertEquals($rendered, $template->renderInternal($context));
        $this->assertEquals($rendered, $template->render(array('foo' => 'bar')));
        $this->assertEquals($rendered, $template->render(array('foo' => 'bar'), $global_variables));
    }
}

class Mustache_Test_TemplateStub extends Mustache_Template
{
    public $rendered;

    public function getMustache()
    {
        return $this->mustache;
    }

    public function renderInternal(Mustache_Context $context, $indent = '', $escape = false)
    {
        return $this->rendered;
    }
}
