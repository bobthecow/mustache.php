<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2015 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Mustache_Test_Cache_AbstractCacheTest extends PHPUnit_Framework_TestCase
{
    public function testGetSetLogger()
    {
        $cache  = new CacheStub();
        $logger = new Mustache_Logger_StreamLogger('php://stdout');
        $cache->setLogger($logger);
        $this->assertSame($logger, $cache->getLogger());
    }

    /**
     * @expectedException Mustache_Exception_InvalidArgumentException
     */
    public function testSetLoggerThrowsExceptions()
    {
        $cache  = new CacheStub();
        $logger = new StdClass();
        $cache->setLogger($logger);
    }
}

class CacheStub extends Mustache_Cache_AbstractCache
{
    public function load($key)
    {
        // nada
    }

    public function cache($key, $value)
    {
        // nada
    }
}
