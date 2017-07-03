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
 * @group magic_methods
 * @group functional
 */
class Mustache_Test_Functional_CallTest extends PHPUnit_Framework_TestCase
{
    public function testCallEatsContext()
    {
        $m = new Mustache_Engine();
        $tpl = $m->loadTemplate('{{# foo }}{{ label }}: {{ name }}{{/ foo }}');

        $foo = new Mustache_Test_Functional_ClassWithCall();
        $foo->name = 'Bob';

        $data = array('label' => 'name', 'foo' => $foo);

        $this->assertEquals('name: Bob', $tpl->render($data));
    }
}

class Mustache_Test_Functional_ClassWithCall
{
    public $name;

    public function __call($method, $args)
    {
        return 'unknown value';
    }
}
