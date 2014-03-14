<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Mustache_Test_Exception_UnknownTemplateExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $e = new Mustache_Exception_UnknownTemplateException('mario');
        $this->assertTrue($e instanceof InvalidArgumentException);
        $this->assertTrue($e instanceof Mustache_Exception);
    }

    public function testMessage()
    {
        $e = new Mustache_Exception_UnknownTemplateException('luigi');
        $this->assertEquals('Unknown template: luigi', $e->getMessage());
    }

    public function testGetTemplateName()
    {
        $e = new Mustache_Exception_UnknownTemplateException('yoshi');
        $this->assertEquals('yoshi', $e->getTemplateName());
    }
}
