<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group unit
 */
class Mustache_Test_Loader_ArrayLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $loader = new Mustache_Loader_ArrayLoader(array(
            'foo' => 'bar'
        ));

        $this->assertEquals('bar', $loader->load('foo'));
    }

    public function testSetAndLoadTemplates()
    {
        $loader = new Mustache_Loader_ArrayLoader(array(
            'foo' => 'bar'
        ));
        $this->assertEquals('bar', $loader->load('foo'));

        $loader->setTemplate('baz', 'qux');
        $this->assertEquals('qux', $loader->load('baz'));

        $loader->setTemplates(array(
            'foo' => 'FOO',
            'baz' => 'BAZ',
        ));
        $this->assertEquals('FOO', $loader->load('foo'));
        $this->assertEquals('BAZ', $loader->load('baz'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissingTemplatesThrowExceptions()
    {
        $loader = new Mustache_Loader_ArrayLoader;
        $loader->load('not_a_real_template');
    }
}
