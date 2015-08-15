<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2015 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group functional
 */
class Mustache_Test_Cache_FilesystemCacheTest extends Mustache_Test_FunctionalTestCase
{
    public function testCacheGetNone()
    {
        $key = 'some key';
        $cache = new Mustache_Cache_FilesystemCache(self::$tempDir);
        $loaded = $cache->load($key);

        $this->assertFalse($loaded);
    }

    public function testCachePut()
    {
        $key = 'some key';
        $value = '<?php /* some value */';
        $cache = new Mustache_Cache_FilesystemCache(self::$tempDir);
        $cache->cache($key, $value);
        $loaded = $cache->load($key);

        $this->assertTrue($loaded);
    }
}
