<?php
namespace Mustache\Test\Exception;

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class UnknownFilterExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $e = new \Mustache\Exception\UnknownFilterException('bacon');
        $this->assertTrue($e instanceof \UnexpectedValueException);
        $this->assertTrue($e instanceof \Mustache\Exception);
    }

    public function testMessage()
    {
        $e = new \Mustache\Exception\UnknownFilterException('sausage');
        $this->assertEquals('Unknown filter: sausage', $e->getMessage());
    }

    public function testGetFilterName()
    {
        $e = new \Mustache\Exception\UnknownFilterException('eggs');
        $this->assertEquals('eggs', $e->getFilterName());
    }
}
