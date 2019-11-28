<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2017 Enalean
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Mustache_Test_Exception_UnknownVariableExceptionTest extends PHPUnit_Framework_TestCase
{

    public function testInstance()
    {
        $e = new Mustache_Exception_UnknownVariableException('alpha');
        $this->assertTrue($e instanceof UnexpectedValueException);
        $this->assertTrue($e instanceof Mustache_Exception);
    }

    public function testMessage()
    {
        $e = new Mustache_Exception_UnknownVariableException('beta');
        $this->assertEquals('Unknown variable: beta', $e->getMessage());
    }

    public function testGetHelperName()
    {
        $e = new Mustache_Exception_UnknownVariableException('gamma');
        $this->assertEquals('gamma', $e->getVariableName());
    }

    public function testPrevious()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $this->markTestSkipped('Exception chaining requires at least PHP 5.3');
        }

        $previous = new Exception();
        $e = new Mustache_Exception_UnknownVariableException('foo', $previous);
        $this->assertSame($previous, $e->getPrevious());
    }
}
