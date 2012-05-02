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
class Mustache_Test_EngineTest extends PHPUnit_Framework_TestCase
{

    private static $tempDir;

    public static function setUpBeforeClass()
    {
        self::$tempDir = sys_get_temp_dir() . '/mustache_test';
        if (file_exists(self::$tempDir)) {
            self::rmdir(self::$tempDir);
        }
    }

    public function testConstructor()
    {
        $loader         = new Mustache_Loader_StringLoader;
        $partialsLoader = new Mustache_Loader_ArrayLoader;
        $mustache       = new Mustache_Engine(array(
            'template_class_prefix' => '__whot__',
            'cache' => self::$tempDir,
            'loader' => $loader,
            'partials_loader' => $partialsLoader,
            'partials' => array(
                'foo' => '{{ foo }}',
            ),
            'helpers' => array(
                'foo' => array($this, 'getFoo'),
                'bar' => 'BAR',
            ),
            'escape'  => 'strtoupper',
            'charset' => 'ISO-8859-1',
        ));

        $this->assertSame($loader, $mustache->getLoader());
        $this->assertSame($partialsLoader, $mustache->getPartialsLoader());
        $this->assertEquals('{{ foo }}', $partialsLoader->load('foo'));
        $this->assertContains('__whot__', $mustache->getTemplateClassName('{{ foo }}'));
        $this->assertEquals('strtoupper', $mustache->getEscape());
        $this->assertEquals('ISO-8859-1', $mustache->getCharset());
        $this->assertTrue($mustache->hasHelper('foo'));
        $this->assertTrue($mustache->hasHelper('bar'));
        $this->assertFalse($mustache->hasHelper('baz'));
    }

    public static function getFoo()
    {
        return 'foo';
    }

    public function testRender()
    {
        $source = '{{ foo }}';
        $data   = array('bar' => 'baz');
        $output = 'TEH OUTPUT';

        $template = $this->getMockBuilder('Mustache_Template')
            ->disableOriginalConstructor()
            ->getMock();

        $mustache = new MustacheStub;
        $mustache->template = $template;

        $template->expects($this->once())
            ->method('render')
            ->with($data)
            ->will($this->returnValue($output));

        $this->assertEquals($output, $mustache->render($source, $data));
        $this->assertEquals($source, $mustache->source);
    }

    public function testSettingServices()
    {
        $loader    = new Mustache_Loader_StringLoader;
        $tokenizer = new Mustache_Tokenizer;
        $parser    = new Mustache_Parser;
        $compiler  = new Mustache_Compiler;
        $mustache  = new Mustache_Engine;

        $this->assertNotSame($loader, $mustache->getLoader());
        $mustache->setLoader($loader);
        $this->assertSame($loader, $mustache->getLoader());

        $this->assertNotSame($loader, $mustache->getPartialsLoader());
        $mustache->setPartialsLoader($loader);
        $this->assertSame($loader, $mustache->getPartialsLoader());

        $this->assertNotSame($tokenizer, $mustache->getTokenizer());
        $mustache->setTokenizer($tokenizer);
        $this->assertSame($tokenizer, $mustache->getTokenizer());

        $this->assertNotSame($parser, $mustache->getParser());
        $mustache->setParser($parser);
        $this->assertSame($parser, $mustache->getParser());

        $this->assertNotSame($compiler, $mustache->getCompiler());
        $mustache->setCompiler($compiler);
        $this->assertSame($compiler, $mustache->getCompiler());
    }

    /**
     * @group functional
     */
    public function testCache()
    {
        $mustache = new Mustache_Engine(array(
            'template_class_prefix' => '__whot__',
            'cache' => self::$tempDir,
        ));

        $source    = '{{ foo }}';
        $template  = $mustache->loadTemplate($source);
        $className = $mustache->getTemplateClassName($source);
        $fileName  = self::$tempDir . '/' . $className . '.php';
        $this->assertInstanceOf($className, $template);
        $this->assertFileExists($fileName);
        $this->assertContains("\nclass $className extends Mustache_Template", file_get_contents($fileName));
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider getBadEscapers
     */
    public function testNonCallableEscapeThrowsException($escape)
    {
        new Mustache_Engine(array('escape' => $escape));
    }

    public function getBadEscapers()
    {
        return array(
            array('nothing'),
            array('foo', 'bar'),
        );
    }

    /**
     * @expectedException RuntimeException
     */
    public function testImmutablePartialsLoadersThrowException()
    {
        $mustache = new Mustache_Engine(array(
            'partials_loader' => new Mustache_Loader_StringLoader,
        ));

        $mustache->setPartials(array('foo' => '{{ foo }}'));
    }

    public function testMissingPartialsTreatedAsEmptyString()
    {
        $mustache = new Mustache_Engine(array(
            'partials_loader' => new Mustache_Loader_ArrayLoader(array(
                'foo' => 'FOO',
                'baz' => 'BAZ',
            ))
        ));

        $this->assertEquals('FOOBAZ', $mustache->render('{{>foo}}{{>bar}}{{>baz}}', array()));
    }

    public function testHelpers()
    {
        $foo = array($this, 'getFoo');
        $bar = 'BAR';
        $mustache = new Mustache_Engine(array('helpers' => array(
            'foo' => $foo,
            'bar' => $bar,
        )));

        $helpers = $mustache->getHelpers();
        $this->assertTrue($mustache->hasHelper('foo'));
        $this->assertTrue($mustache->hasHelper('bar'));
        $this->assertTrue($helpers->has('foo'));
        $this->assertTrue($helpers->has('bar'));
        $this->assertSame($foo, $mustache->getHelper('foo'));
        $this->assertSame($bar, $mustache->getHelper('bar'));

        $mustache->removeHelper('bar');
        $this->assertFalse($mustache->hasHelper('bar'));
        $mustache->addHelper('bar', $bar);
        $this->assertSame($bar, $mustache->getHelper('bar'));

        $baz = array($this, 'wrapWithUnderscores');
        $this->assertFalse($mustache->hasHelper('baz'));
        $this->assertFalse($helpers->has('baz'));

        $mustache->addHelper('baz', $baz);
        $this->assertTrue($mustache->hasHelper('baz'));
        $this->assertTrue($helpers->has('baz'));

        // ... and a functional test
        $tpl = $mustache->loadTemplate('{{foo}} - {{bar}} - {{#baz}}qux{{/baz}}');
        $this->assertEquals('foo - BAR - __qux__', $tpl->render());
        $this->assertEquals('foo - BAR - __qux__', $tpl->render(array('qux' => "won't mess things up")));
    }

    public static function wrapWithUnderscores($text)
    {
        return '__'.$text.'__';
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetHelpersThrowsExceptions()
    {
        $mustache = new Mustache_Engine;
        $mustache->setHelpers('monkeymonkeymonkey');
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

class MustacheStub extends Mustache_Engine {
    public $source;
    public $template;
    public function loadTemplate($source)
    {
        $this->source = $source;

        return $this->template;
    }
}
