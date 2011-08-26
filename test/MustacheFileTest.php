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
                $basedir = dirname(__FILE__) . '/../examples/';

                $m = new Mustache();

                $this->assertEquals(file_get_contents($basedir . 'simple/simple.txt'), $m->renderFile($basedir . 'simple/simple.mustache'));
                
                try {
                       $m->renderFile(null);
                } catch (Exception $exc) {
                        $this->assertInstanceOf(InvalidArgumentException, $exc);
                }
                
                try {
                       $m->renderFile($basedir.'bogus/bogus.mustache');
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
        public function testRenderPartialDir() {
                $basedir = dirname(__FILE__) . '/../examples/';
                
                $m = new Mustache();
                
                
        }

}
