<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group unit
 */
class Mustache_Test_Loader_FilesystemLoaderTest extends PHPUnit_Framework_TestCase
{
    private static $_tempDir, $_tmp_alpha, $_tmp_beta;

    public static function tearDownAfterClass()
    {
        // clean up files for testConstructorWithProtocol in case test fails
        if (is_file(self::$_tmp_alpha)) {
            unlink(self::$_tmp_alpha);
        }
        if (is_file(self::$_tmp_beta)) {
            unlink(self::$_tmp_beta);
        }
        if (is_dir(self::$_tempDir)) {
            rmdir(self::$_tempDir);
        }
        if (is_file(self::$_tempDir)) {
            unlink(self::$_tempDir);
        }
    }

    public function testConstructor()
    {
        $baseDir = realpath(dirname(__FILE__).'/../../../fixtures/templates');
        $loader = new Mustache_Loader_FilesystemLoader($baseDir, array('extension' => '.ms'));
        $this->assertEquals('alpha contents', $loader->load('alpha'));
        $this->assertEquals('beta contents', $loader->load('beta.ms'));
    }

    public function testConstructorWithProtocol()
    {
        $baseDir        = realpath(dirname(__FILE__).'/../../../fixtures/templates');
        self::$_tempDir = tempnam(sys_get_temp_dir(), '');

        // starts as a file, so unlink
        if (file_exists(self::$_tempDir)) {
            unlink(self::$_tempDir);
        }

        if (mkdir(self::$_tempDir)) {
            self::$_tmp_alpha = self::$_tempDir . '/alpha.ms';
            self::$_tmp_beta  = self::$_tempDir . '/beta.ms';

            // copy to tempDir
            copy($baseDir . '/alpha.ms', self::$_tmp_alpha);
            copy($baseDir . '/beta.ms', self::$_tmp_beta);

            $loader = new Mustache_Loader_FilesystemLoader('file://' . self::$_tempDir, array('extension' => '.ms'));
            $this->assertEquals('alpha contents', $loader->load('alpha'));
            $this->assertEquals('beta contents', $loader->load('beta.ms'));
        }
    }

    public function testLoadTemplates()
    {
        $baseDir = realpath(dirname(__FILE__).'/../../../fixtures/templates');
        $loader = new Mustache_Loader_FilesystemLoader($baseDir);
        $this->assertEquals('one contents', $loader->load('one'));
        $this->assertEquals('two contents', $loader->load('two.mustache'));
    }

    public function testEmptyExtensionString()
    {
        $baseDir = realpath(dirname(__FILE__).'/../../../fixtures/templates');

        $loader = new Mustache_Loader_FilesystemLoader($baseDir, array('extension' => ''));
        $this->assertEquals('one contents', $loader->load('one.mustache'));
        $this->assertEquals('alpha contents', $loader->load('alpha.ms'));

        $loader = new Mustache_Loader_FilesystemLoader($baseDir, array('extension' => null));
        $this->assertEquals('two contents', $loader->load('two.mustache'));
        $this->assertEquals('beta contents', $loader->load('beta.ms'));
    }

    /**
     * @expectedException Mustache_Exception_RuntimeException
     */
    public function testMissingBaseDirThrowsException()
    {
        $loader = new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/not_a_directory');
    }

    /**
     * @expectedException Mustache_Exception_UnknownTemplateException
     */
    public function testMissingTemplateThrowsException()
    {
        $baseDir = realpath(dirname(__FILE__).'/../../../fixtures/templates');
        $loader = new Mustache_Loader_FilesystemLoader($baseDir);

        $loader->load('fake');
    }
}
