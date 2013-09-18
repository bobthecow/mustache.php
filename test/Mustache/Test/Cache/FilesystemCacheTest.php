<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2013 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group functional
 */
class Mustache_Test_Cache_FilesystemCacheTest extends PHPUnit_Framework_TestCase
{
    private static $tempDir;

    public static function setUpBeforeClass()
    {
        self::$tempDir = sys_get_temp_dir() . '/mustache_test';
        if (file_exists(self::$tempDir)) {
            self::rmdir(self::$tempDir);
        }
    }

    public function testCacheGetNone()
    {
        $key = 'some key';
        $cache = new Mustache_Cache_FilesystemCache(self::$tempDir);;
        $loaded = $cache->load($key);

        $this->assertFalse($loaded);
    }

    public function testCachePut()
    {
        $key = 'some key';
        $value = '<?php /* some value */';
        $cache = new Mustache_Cache_FilesystemCache(self::$tempDir);;
        $cache->cache($key, $value);
        $loaded = $cache->load($key);

        $this->assertTrue($loaded);
    }

    private static function rmdir($path)
    {
        $path = rtrim($path, '/').'/';
        $handle = opendir($path);
        while (($file = readdir($handle)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $fullpath = $path.$file;
            if (is_dir($fullpath)) {
                self::rmdir($fullpath);
            } else {
                unlink($fullpath);
            }
        }

        closedir($handle);
        rmdir($path);
    }
}
