<?php

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
        $cached = $cache->get($key);

        $this->assertNull($cached);
    }

    public function testCachePut()
    {
        $key = 'some key';
        $value = 'some value';
        $cache = new Mustache_Cache_FilesystemCache(self::$tempDir);;
        $cache->put($key, $value);
        $cached = $cache->get($key);

        $this->assertEquals($cached, $value);
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
