<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2017 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class Mustache_Test_SpecTestCase extends \PHPUnit\Framework\TestCase
{
    protected static $mustache;

    public static function setUpBeforeClass(): void
    {
        self::$mustache = new Mustache_Engine();
    }

    protected static function loadTemplate($source, $partials)
    {
        self::$mustache->setPartials($partials);

        return self::$mustache->loadTemplate($source);
    }

    /**
     * Data provider for the mustache spec test.
     *
     * Loads JSON files from the spec and converts them to PHPisms.
     *
     * @param string $name
     *
     * @return array
     */
    protected function loadSpec($name)
    {
        $filename = dirname(__FILE__) . '/../../../vendor/spec/specs/' . $name . '.json';
        if (!file_exists($filename)) {
            return array();
        }

        $data = array();
        $file = file_get_contents($filename);
        $spec = json_decode($file, true);

        foreach ($spec['tests'] as $test) {
            $data[] = array(
                $test['name'] . ': ' . $test['desc'],
                $test['template'],
                isset($test['partials']) ? $test['partials'] : array(),
                $test['data'],
                $test['expected'],
            );
        }

        return $data;
    }
}
