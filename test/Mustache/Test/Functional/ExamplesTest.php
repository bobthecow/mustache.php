<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2016 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group examples
 * @group functional
 */
class Mustache_Test_Functional_ExamplesTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test everything in the `examples` directory.
     *
     * @dataProvider getExamples
     *
     * @param string $context
     * @param string $source
     * @param array  $partials
     * @param string $expected
     */
    public function testExamples($context, $source, $partials, $expected)
    {
        $mustache = new Mustache_Engine(array(
            'partials' => $partials,
        ));
        $this->assertEquals($expected, $mustache->loadTemplate($source)->render($context));
    }

    /**
     * Data provider for testExamples method.
     *
     * Loads examples from the test fixtures directory.
     *
     * This examples directory should contain any number of subdirectories, each of which contains
     * three files: one Mustache class (.php), one Mustache template (.mustache), and one output file
     * (.txt). Optionally, the directory may contain a folder full of partials.
     *
     * @return array
     */
    public function getExamples()
    {
        $path     = realpath(dirname(__FILE__) . '/../../../fixtures/examples');
        $examples = array();

        $handle   = opendir($path);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullpath = $path . '/' . $file;
            if (is_dir($fullpath)) {
                $examples[$file] = $this->loadExample($fullpath);
            }
        }
        closedir($handle);

        return $examples;
    }

    /**
     * Helper method to load an example given the full path.
     *
     * @param string $path
     *
     * @return array arguments for testExamples
     */
    private function loadExample($path)
    {
        $context  = null;
        $source   = null;
        $partials = array();
        $expected = null;

        $handle = opendir($path);
        while (($file = readdir($handle)) !== false) {
            $fullpath = $path . '/' . $file;
            $info = pathinfo($fullpath);

            if (is_dir($fullpath) && $info['basename'] === 'partials') {
                // load partials
                $partials = $this->loadPartials($fullpath);
            } elseif (is_file($fullpath)) {
                // load other files
                switch ($info['extension']) {
                    case 'php':
                        require_once $fullpath;
                        $className = $info['filename'];
                        $context   = new $className();
                        break;

                    case 'mustache':
                        $source   = file_get_contents($fullpath);
                        break;

                    case 'txt':
                        $expected = file_get_contents($fullpath);
                        break;
                }
            }
        }
        closedir($handle);

        return array($context, $source, $partials, $expected);
    }

    /**
     * Helper method to load partials given an example directory.
     *
     * @param string $path
     *
     * @return array $partials
     */
    private function loadPartials($path)
    {
        $partials = array();

        $handle = opendir($path);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullpath = $path . '/' . $file;
            $info = pathinfo($fullpath);

            if ($info['extension'] === 'mustache') {
                $partials[$info['filename']] = file_get_contents($fullpath);
            }
        }
        closedir($handle);

        return $partials;
    }
}
