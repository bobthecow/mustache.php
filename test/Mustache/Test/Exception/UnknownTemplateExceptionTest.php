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

class UnknownTemplateExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $e = new \Mustache\Exception\UnknownTemplateException('mario');
        $this->assertTrue($e instanceof \InvalidArgumentException);
        $this->assertTrue($e instanceof \Mustache\Exception);
    }

    public function testMessage()
    {
        $e = new \Mustache\Exception\UnknownTemplateException('luigi');
        $this->assertEquals('Unknown template: luigi', $e->getMessage());
    }

    public function testGetTemplateName()
    {
        $e = new \Mustache\Exception\UnknownTemplateException('yoshi');
        $this->assertEquals('yoshi', $e->getTemplateName());
    }
}
