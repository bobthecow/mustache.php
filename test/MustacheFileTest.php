<?php

require_once '../Mustache.php';

/**
 * A PHPUnit test case wrapping the Mustache File Loading and rendering
 *
 * @author Cameron Bytheway <bytheway.cameron@gmail.com>
 * @group mustache-spec
 */
class MustacheFileTest extends PHPUnit_Framework_TestCase {

        /**
         * Test renderFile($filename).
         *
         * @access public
         * @return void
         */
        public function testRenderFile() {
                $basedir = dirname(__FILE__) . '/../examples';

                $m = new Mustache();
                
                require_once $basedir . '/simple/Simple.php';

                $this->assertEquals(file_get_contents($basedir . '/simple/simple.txt'), $m->renderFile($basedir . '/simple/simple.mustache', new Simple()));
                
                try {
                       $m->renderFile(null);
                       $this->fail('Mustache should throw an exception when trying to render a "null" file');
                } catch (Exception $exc) {
                        $this->assertInstanceOf('InvalidArgumentException', $exc);
                }
                
                try {
                       $m->renderFile($basedir.'/bogus/bogus.mustache');
                       $this->fail('Mustache should throw an exception when trying to render a non-existant file');
                } catch (Exception $exc) {
                        $this->assertInstanceOf('InvalidArgumentException', $exc);
                }
        }
        
        /**
         * Tests rendering a directory of partials
         * 
         * @access public
         * @return void
         */
        public function testRenderPartialsInDir() {
                $basedir = dirname(__FILE__) . '/../examples/file_render';
                
                $m = new Mustache();
                
                // Mustache sound be able to look in the same directory for the required partials
                $this->assertEquals(file_get_contents($basedir . '/file_render.txt'), $m->renderFile($basedir.'/file_render.mustache'));
        }
        
        /**
         * Tests rendering a directory of partials recursively
         * 
         * @access public
         * @return void
         */
        public function testRenderPartialsInDirRecursive() {
                $basedir = dirname(__FILE__) . '/../examples/file_render_recursive';
                
                $m = new Mustache();
                
                $m->setPartialRecursiveSearch(TRUE);
                
                $this->assertEquals(file_get_contents($basedir . '/file_render_recursive.txt'), $m->renderFile($basedir.'/file_render_recursive.mustache'));
        }
        
        /**
         * Tests the ability to render partials outside of the template being rendered
         * 
         * @access public
         * @return void
         */
        public function testRenderSetPartialDir() {
                $basedir = dirname(__FILE__) . '/../examples';
                
                $m = new Mustache();
                
                // Add a directory for mustache to search
                $m->addPartialDirectory($basedir.'/file_render');
                
                $this->assertEquals(file_get_contents($basedir . '/file_render_external/file_render_external.txt'), $m->renderFile($basedir.'/file_render_external/file_render_external.mustache'));
        }

}
