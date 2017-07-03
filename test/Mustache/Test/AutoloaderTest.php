<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2017 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group unit
 */
class Mustache_Test_AutoloaderTest extends PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $loader = Mustache_Autoloader::register();
        $this->assertTrue(spl_autoload_unregister(array($loader, 'autoload')));
    }

    public function testAutoloader()
    {
        $loader = new Mustache_Autoloader(dirname(__FILE__) . '/../../fixtures/autoloader');

        $this->assertNull($loader->autoload('NonMustacheClass'));
        $this->assertFalse(class_exists('NonMustacheClass'));

        $loader->autoload('Mustache_Foo');
        $this->assertTrue(class_exists('Mustache_Foo'));

        $loader->autoload('\Mustache_Bar');
        $this->assertTrue(class_exists('Mustache_Bar'));
    }

    /**
     * Test that the autoloader won't register multiple times.
     */
    public function testRegisterMultiple()
    {
        $numLoaders = count(spl_autoload_functions());

        Mustache_Autoloader::register();
        Mustache_Autoloader::register();

        $expectedNumLoaders = $numLoaders + 1;

        $this->assertCount($expectedNumLoaders, spl_autoload_functions());
    }
}
