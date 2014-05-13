<?php
namespace Mustache\Test;

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group unit
 */
class ContextTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $one = new \Mustache\Context;
        $this->assertSame('', $one->find('foo'));
        $this->assertSame('', $one->find('bar'));

        $two = new \Mustache\Context(array(
            'foo' => 'FOO',
            'bar' => '<BAR>'
        ));
        $this->assertEquals('FOO', $two->find('foo'));
        $this->assertEquals('<BAR>', $two->find('bar'));

        $obj = new \StdClass;
        $obj->name = 'NAME';
        $three = new \Mustache\Context($obj);
        $this->assertSame($obj, $three->last());
        $this->assertEquals('NAME', $three->find('name'));
    }

    public function testPushPopAndLast()
    {
        $context = new \Mustache\Context;
        $this->assertFalse($context->last());

        $dummy = new \Mustache\Test\TestDummy;
        $context->push($dummy);
        $this->assertSame($dummy, $context->last());
        $this->assertSame($dummy, $context->pop());
        $this->assertFalse($context->last());

        $obj = new \StdClass;
        $context->push($dummy);
        $this->assertSame($dummy, $context->last());
        $context->push($obj);
        $this->assertSame($obj, $context->last());
        $this->assertSame($obj, $context->pop());
        $this->assertSame($dummy, $context->pop());
        $this->assertFalse($context->last());
    }

    public function testFind()
    {
        $context = new \Mustache\Context;

        $dummy = new \Mustache\Test\TestDummy;

        $obj = new \StdClass;
        $obj->name = 'obj';

        $arr = array(
            'a' => array('b' => array('c' => 'see')),
            'b' => 'bee',
        );

        $string = 'some arbitrary string';

        $context->push($dummy);
        $this->assertEquals('dummy', $context->find('name'));

        $context->push($obj);
        $this->assertEquals('obj', $context->find('name'));

        $context->pop();
        $this->assertEquals('dummy', $context->find('name'));

        $dummy->name = 'dummyer';
        $this->assertEquals('dummyer', $context->find('name'));

        $context->push($arr);
        $this->assertEquals('bee', $context->find('b'));
        $this->assertEquals('see', $context->findDot('a.b.c'));

        $dummy->name = 'dummy';

        $context->push($string);
        $this->assertSame($string, $context->last());
        $this->assertEquals('dummy', $context->find('name'));
        $this->assertEquals('see', $context->findDot('a.b.c'));
        $this->assertEquals('<foo>', $context->find('foo'));
        $this->assertEquals('<bar>', $context->findDot('bar'));
    }

    public function testArrayAccessFind()
    {
        $access = new \Mustache\Test\TestArrayAccess(array(
            'a' => array('b' => array('c' => 'see')),
            'b' => 'bee',
        ));

        $context = new \Mustache\Context($access);
        $this->assertEquals('bee', $context->find('b'));
        $this->assertEquals('see', $context->findDot('a.b.c'));
        $this->assertEquals(null, $context->findDot('a.b.c.d'));
    }

    public function testAccessorPriority()
    {
        $context = new \Mustache\Context(new \Mustache\Test\AllTheThings);

        $this->assertEquals('win', $context->find('foo'), 'method beats property');
        $this->assertEquals('win', $context->find('bar'), 'property beats ArrayAccess');
        $this->assertEquals('win', $context->find('baz'), 'ArrayAccess stands alone');
        $this->assertEquals('win', $context->find('qux'), 'ArrayAccess beats private property');
    }
}

class TestDummy
{
    public $name = 'dummy';

    public function __invoke()
    {
        // nothing
    }

    public static function foo()
    {
        return '<foo>';
    }

    public function bar()
    {
        return '<bar>';
    }
}

class TestArrayAccess implements \ArrayAccess
{
    private $container = array();

    public function __construct($array)
    {
        foreach ($array as $key => $value) {
            $this->container[$key] = $value;
        }
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
}

class AllTheThings implements \ArrayAccess
{
    public $foo  = 'fail';
    public $bar  = 'win';
    private $qux = 'fail';

    public function foo()
    {
        return 'win';
    }

    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetGet($offset)
    {
        switch ($offset) {
            case 'foo':
            case 'bar':
                return 'fail';

            case 'baz':
            case 'qux':
                return 'win';

            default:
                return 'lolwhut';
        }
    }

    public function offsetSet($offset, $value)
    {
        // nada
    }

    public function offsetUnset($offset)
    {
        // nada
    }
}
