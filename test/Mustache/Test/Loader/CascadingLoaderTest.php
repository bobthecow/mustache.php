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
class Mustache_Test_Loader_CascadingLoaderTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadTemplates()
    {
        $loader = new Mustache_Loader_CascadingLoader(array(
            new Mustache_Loader_ArrayLoader(array('foo' => '{{ foo }}')),
            new Mustache_Loader_ArrayLoader(array('bar' => '{{#bar}}BAR{{/bar}}')),
        ));

        $this->assertEquals('{{ foo }}', $loader->load('foo'));
        $this->assertEquals('{{#bar}}BAR{{/bar}}', $loader->load('bar'));
    }

    public function testMissingTemplatesThrowExceptions()
    {
        $this->expectException(Mustache_Exception_UnknownTemplateException::class);

        $loader = new Mustache_Loader_CascadingLoader(array(
            new Mustache_Loader_ArrayLoader(array('foo' => '{{ foo }}')),
            new Mustache_Loader_ArrayLoader(array('bar' => '{{#bar}}BAR{{/bar}}')),
        ));

        $loader->load('not_a_real_template');
    }
}
