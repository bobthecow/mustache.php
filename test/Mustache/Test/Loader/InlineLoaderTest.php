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
class InlineLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadTemplates()
    {
        $loader = new \Mustache\Loader\InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__);
        $this->assertEquals('{{ foo }}', $loader->load('foo'));
        $this->assertEquals('{{#bar}}BAR{{/bar}}', $loader->load('bar'));
    }

    /**
     * @expectedException \Mustache\Exception\UnknownTemplateException
     */
    public function testMissingTemplatesThrowExceptions()
    {
        $loader = new \Mustache\Loader\InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__);
        $loader->load('not_a_real_template');
    }

    /**
     * @expectedException \Mustache\Exception\InvalidArgumentException
     */
    public function testInvalidOffsetThrowsException()
    {
        new \Mustache\Loader\InlineLoader(__FILE__, 'notanumber');
    }

    /**
     * @expectedException \Mustache\Exception\InvalidArgumentException
     */
    public function testInvalidFileThrowsException()
    {
        new \Mustache\Loader\InlineLoader('notarealfile', __COMPILER_HALT_OFFSET__);
    }
}

__halt_compiler();

@@ foo
{{ foo }}

@@ bar
{{#bar}}BAR{{/bar}}
