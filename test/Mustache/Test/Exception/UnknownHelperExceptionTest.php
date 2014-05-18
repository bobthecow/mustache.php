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

class UnknownHelperExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $e = new \Mustache\Exception\UnknownHelperException('alpha');
        $this->assertTrue($e instanceof \InvalidArgumentException);
        $this->assertTrue($e instanceof \Mustache\Exception);
    }

    public function testMessage()
    {
        $e = new \Mustache\Exception\UnknownHelperException('beta');
        $this->assertEquals('Unknown helper: beta', $e->getMessage());
    }

    public function testGetHelperName()
    {
        $e = new \Mustache\Exception\UnknownHelperException('gamma');
        $this->assertEquals('gamma', $e->getHelperName());
    }
}
