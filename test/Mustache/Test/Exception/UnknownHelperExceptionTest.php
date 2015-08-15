<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2015 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Mustache_Test_Exception_UnknownHelperExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $e = new Mustache_Exception_UnknownHelperException('alpha');
        $this->assertTrue($e instanceof InvalidArgumentException);
        $this->assertTrue($e instanceof Mustache_Exception);
    }

    public function testMessage()
    {
        $e = new Mustache_Exception_UnknownHelperException('beta');
        $this->assertEquals('Unknown helper: beta', $e->getMessage());
    }

    public function testGetHelperName()
    {
        $e = new Mustache_Exception_UnknownHelperException('gamma');
        $this->assertEquals('gamma', $e->getHelperName());
    }
}
