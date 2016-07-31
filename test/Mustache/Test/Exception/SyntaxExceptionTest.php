<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2016 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Mustache_Test_Exception_SyntaxExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $e = new Mustache_Exception_SyntaxException('whot', array('is' => 'this'));
        $this->assertTrue($e instanceof LogicException);
        $this->assertTrue($e instanceof Mustache_Exception);
    }

    public function testGetToken()
    {
        $token = array(Mustache_Tokenizer::TYPE => 'whatever');
        $e = new Mustache_Exception_SyntaxException('ignore this', $token);
        $this->assertEquals($token, $e->getToken());
    }

    public function testPrevious()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $this->markTestSkipped('Exception chaining requires at least PHP 5.3');
        }

        $previous = new Exception();
        $e = new Mustache_Exception_SyntaxException('foo', array(), $previous);

        $this->assertSame($previous, $e->getPrevious());
    }
}
