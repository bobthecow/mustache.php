<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2013 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Mustache_Test_Exception_UnknownFilterExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $e = new Mustache_Exception_UnknownFilterException('bacon');
        $this->assertTrue($e instanceof UnexpectedValueException);
        $this->assertTrue($e instanceof Mustache_Exception);
    }

    public function testMessage()
    {
        $e = new Mustache_Exception_UnknownFilterException('sausage');
        $this->assertEquals('Unknown filter: sausage', $e->getMessage());
    }

    public function testGetFilterName()
    {
        $e = new Mustache_Exception_UnknownFilterException('eggs');
        $this->assertEquals('eggs', $e->getFilterName());
    }
}
