<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2015 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Mustache_Test_HelperCollectionTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $foo = array($this, 'getFoo');
        $bar = 'BAR';

        $helpers = new Mustache_HelperCollection(array(
            'foo' => $foo,
            'bar' => $bar,
        ));

        $this->assertSame($foo, $helpers->get('foo'));
        $this->assertSame($bar, $helpers->get('bar'));
    }

    public static function getFoo()
    {
        echo 'foo';
    }

    public function testAccessorsAndMutators()
    {
        $foo = array($this, 'getFoo');
        $bar = 'BAR';

        $helpers = new Mustache_HelperCollection();
        $this->assertTrue($helpers->isEmpty());
        $this->assertFalse($helpers->has('foo'));
        $this->assertFalse($helpers->has('bar'));

        $helpers->add('foo', $foo);
        $this->assertFalse($helpers->isEmpty());
        $this->assertTrue($helpers->has('foo'));
        $this->assertFalse($helpers->has('bar'));

        $helpers->add('bar', $bar);
        $this->assertFalse($helpers->isEmpty());
        $this->assertTrue($helpers->has('foo'));
        $this->assertTrue($helpers->has('bar'));

        $helpers->remove('foo');
        $this->assertFalse($helpers->isEmpty());
        $this->assertFalse($helpers->has('foo'));
        $this->assertTrue($helpers->has('bar'));
    }

    public function testMagicMethods()
    {
        $foo = array($this, 'getFoo');
        $bar = 'BAR';

        $helpers = new Mustache_HelperCollection();
        $this->assertTrue($helpers->isEmpty());
        $this->assertFalse($helpers->has('foo'));
        $this->assertFalse($helpers->has('bar'));
        $this->assertFalse(isset($helpers->foo));
        $this->assertFalse(isset($helpers->bar));

        $helpers->foo = $foo;
        $this->assertFalse($helpers->isEmpty());
        $this->assertTrue($helpers->has('foo'));
        $this->assertFalse($helpers->has('bar'));
        $this->assertTrue(isset($helpers->foo));
        $this->assertFalse(isset($helpers->bar));

        $helpers->bar = $bar;
        $this->assertFalse($helpers->isEmpty());
        $this->assertTrue($helpers->has('foo'));
        $this->assertTrue($helpers->has('bar'));
        $this->assertTrue(isset($helpers->foo));
        $this->assertTrue(isset($helpers->bar));

        unset($helpers->foo);
        $this->assertFalse($helpers->isEmpty());
        $this->assertFalse($helpers->has('foo'));
        $this->assertTrue($helpers->has('bar'));
        $this->assertFalse(isset($helpers->foo));
        $this->assertTrue(isset($helpers->bar));
    }

    /**
     * @dataProvider getInvalidHelperArguments
     */
    public function testHelperCollectionIsntAfraidToThrowExceptions($helpers = array(), $actions = array(), $exception = null)
    {
        if ($exception) {
            $this->setExpectedException($exception);
        }

        $helpers = new Mustache_HelperCollection($helpers);

        foreach ($actions as $method => $args) {
            call_user_func_array(array($helpers, $method), $args);
        }
    }

    public function getInvalidHelperArguments()
    {
        return array(
            array(
                'not helpers',
                array(),
                'InvalidArgumentException',
            ),
            array(
                array(),
                array('get' => array('foo')),
                'InvalidArgumentException',
            ),
            array(
                array('foo' => 'FOO'),
                array('get' => array('foo')),
                null,
            ),
            array(
                array('foo' => 'FOO'),
                array('get' => array('bar')),
                'InvalidArgumentException',
            ),
            array(
                array('foo' => 'FOO'),
                array(
                    'add' => array('bar', 'BAR'),
                    'get' => array('bar'),
                ),
                null,
            ),
            array(
                array('foo' => 'FOO'),
                array(
                    'get'    => array('foo'),
                    'remove' => array('foo'),
                ),
                null,
            ),
            array(
                array('foo' => 'FOO'),
                array(
                    'remove' => array('foo'),
                    'get'    => array('foo'),
                ),
                'InvalidArgumentException',
            ),
            array(
                array(),
                array('remove' => array('foo')),
                'InvalidArgumentException',
            ),
        );
    }
}
