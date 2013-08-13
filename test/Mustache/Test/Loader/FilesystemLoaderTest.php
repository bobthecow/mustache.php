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
    public function testConstructor()
    {
        $baseDir = realpath(dirname(__FILE__).'/../../../fixtures/templates');
        $loader = new Mustache_Loader_FilesystemLoader($baseDir, array('extension' => '.ms'));
        $this->assertEquals('alpha contents', $loader->load('alpha'));
        $this->assertEquals('beta contents', $loader->load('beta.ms'));
    }

    public function testTrailingSlashes()
    {
        $baseDir = dirname(__FILE__).'/../../../fixtures/templates/';
        $loader = new Mustache_Loader_FilesystemLoader($baseDir);
        $this->assertEquals('one contents', $loader->load('one'));
    }

    public function testConstructorWithProtocol()
    {
        $baseDir = realpath(dirname(__FILE__).'/../../../fixtures/templates');

        $loader = new Mustache_Loader_FilesystemLoader('file://' . $baseDir, array('extension' => '.ms'));
        $this->assertEquals('alpha contents', $loader->load('alpha'));
        $this->assertEquals('beta contents', $loader->load('beta.ms'));
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
