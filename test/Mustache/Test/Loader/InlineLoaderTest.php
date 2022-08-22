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
class Mustache_Test_Loader_InlineLoaderTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadTemplates()
    {
        $loader = new Mustache_Loader_InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__);
        $this->assertEquals('{{ foo }}', $loader->load('foo'));
        $this->assertEquals('{{#bar}}BAR{{/bar}}', $loader->load('bar'));
    }

    public function testMissingTemplatesThrowExceptions()
    {
        $loader = new Mustache_Loader_InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__);
        $this->expectException(Mustache_Exception_UnknownTemplateException::class);
        $loader->load('not_a_real_template');
    }

    public function testInvalidOffsetThrowsException()
    {
        $this->expectException(Mustache_Exception_InvalidArgumentException::class);
        new Mustache_Loader_InlineLoader(__FILE__, 'notanumber');
    }

    public function testInvalidFileThrowsException()
    {
        $this->expectException(Mustache_Exception_InvalidArgumentException::class);
        new Mustache_Loader_InlineLoader('notarealfile', __COMPILER_HALT_OFFSET__);
    }
}

__halt_compiler();

@@ foo
{{ foo }}

@@ bar
{{#bar}}BAR{{/bar}}
