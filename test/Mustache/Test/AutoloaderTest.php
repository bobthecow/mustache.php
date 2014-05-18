<?php
namespace Mustache\Test;

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group unit
 */
class AutoloaderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $loader = \Mustache\Autoloader::register();
        $this->assertTrue(spl_autoload_unregister(array($loader, 'autoload')));
    }

    public function testAutoloader()
    {
        $loader = new \Mustache\Autoloader(dirname(__FILE__).'/../../fixtures/autoloader');

        $this->assertNull($loader->autoload('NonMustacheClass'));
        $this->assertFalse(class_exists('NonMustacheClass'));

        $loader->autoload('\Mustache\Foo');
        $this->assertTrue(class_exists('\Mustache\Foo'));

        $loader->autoload('\\Mustache\Bar');
        $this->assertTrue(class_exists('\Mustache\Bar'));
    }
}
