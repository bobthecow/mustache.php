<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group sections
 * @group functional
 */
class Mustache_Test_Functional_ObjectSectionTest extends PHPUnit_Framework_TestCase
{
    private $mustache;

    public function setUp()
    {
        $this->mustache = new Mustache_Engine();
    }

    public function testBasicObject()
    {
        $tpl = $this->mustache->loadTemplate('{{#foo}}{{name}}{{/foo}}');
        $this->assertEquals('Foo', $tpl->render(new Mustache_Test_Functional_Alpha()));
    }

    /**
     * @group magic_methods
     */
    public function testObjectWithGet()
    {
        $tpl = $this->mustache->loadTemplate('{{#foo}}{{name}}{{/foo}}');
        $this->assertEquals('Foo', $tpl->render(new Mustache_Test_Functional_Beta()));
    }

    /**
     * @group magic_methods
     */
    public function testSectionObjectWithGet()
    {
        $tpl = $this->mustache->loadTemplate('{{#bar}}{{#foo}}{{name}}{{/foo}}{{/bar}}');
        $this->assertEquals('Foo', $tpl->render(new Mustache_Test_Functional_Gamma()));
    }

    public function testSectionObjectWithFunction()
    {
        $tpl = $this->mustache->loadTemplate('{{#foo}}{{name}}{{/foo}}');
        $alpha = new Mustache_Test_Functional_Alpha();
        $alpha->foo = new Mustache_Test_Functional_Delta();
        $this->assertEquals('Foo', $tpl->render($alpha));
    }
}

class Mustache_Test_Functional_Alpha
{
    public $foo;

    public function __construct()
    {
        $this->foo = new StdClass();
        $this->foo->name = 'Foo';
        $this->foo->number = 1;
    }
}

class Mustache_Test_Functional_Beta
{
    protected $_data = array();

    public function __construct()
    {
        $this->_data['foo'] = new StdClass();
        $this->_data['foo']->name = 'Foo';
        $this->_data['foo']->number = 1;
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->_data);
    }

    public function __get($name)
    {
        return $this->_data[$name];
    }
}

class Mustache_Test_Functional_Gamma
{
    public $bar;

    public function __construct()
    {
        $this->bar = new Mustache_Test_Functional_Beta();
    }
}

class Mustache_Test_Functional_Delta
{
    protected $_name = 'Foo';

    public function name()
    {
        return $this->_name;
    }
}
