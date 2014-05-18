<?php
namespace Mustache\Test\Loader;

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
class CascadingLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadTemplates()
    {
        $loader = new \Mustache\Loader\CascadingLoader(array(
            new \Mustache\Loader\ArrayLoader(array('foo' => '{{ foo }}')),
            new \Mustache\Loader\ArrayLoader(array('bar' => '{{#bar}}BAR{{/bar}}')),
        ));

        $this->assertEquals('{{ foo }}', $loader->load('foo'));
        $this->assertEquals('{{#bar}}BAR{{/bar}}', $loader->load('bar'));
    }

    /**
     * @expectedException \Mustache\Exception\UnknownTemplateException
     */
    public function testMissingTemplatesThrowExceptions()
    {
        $loader = new \Mustache\Loader\CascadingLoader(array(
            new \Mustache\Loader\ArrayLoader(array('foo' => '{{ foo }}')),
            new \Mustache\Loader\ArrayLoader(array('bar' => '{{#bar}}BAR{{/bar}}')),
        ));

        $loader->load('not_a_real_template');
    }
}
