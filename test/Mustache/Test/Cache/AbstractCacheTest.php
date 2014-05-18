<?php
namespace Mustache\Test\Cache;

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class AbstractCacheTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSetLogger()
    {
         $cache  = new CacheStub();
         $logger = new \Mustache\Logger\StreamLogger('php://stdout');
         $cache->setLogger($logger);
         $this->assertSame($logger, $cache->getLogger());
    }

    /**
     * @expectedException \Mustache\Exception\InvalidArgumentException
     */
    public function testSetLoggerThrowsExceptions()
    {
        $cache  = new CacheStub();
        $logger = new \StdClass();
        $cache->setLogger($logger);
    }
}

class CacheStub extends \Mustache\Cache\AbstractCache
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
