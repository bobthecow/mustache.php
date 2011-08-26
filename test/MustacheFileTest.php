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

                $this->assertEquals(file_get_contents($basedir . '/simple/simple.txt'), $m->renderFile($basedir . '/simple/simple.mustache'));
                
                try {
                       $m->renderFile(null);
                } catch (Exception $exc) {
                        $this->assertInstanceOf(InvalidArgumentException, $exc);
                }
                
                try {
                       $m->renderFile($basedir.'/bogus/bogus.mustache');
                } catch (Exception $exc) {
                        $this->assertInstanceOf(InvalidArgumentException, $exc);
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
                
                // TODO test that Mustache throws an exception when it can't find any partials with that name
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
                
                // Mustache sound be able to look in the same directory for the required partials
                $this->assertEquals(file_get_contents($basedir . '/file_render_recursive.txt'), $m->renderFile($basedir.'/file_render_recursive.mustache'), TRUE);
        }

}
