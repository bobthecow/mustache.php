<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2016 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class Mustache_Test_SpecTestCase extends PHPUnit_Framework_TestCase
{
    protected static $mustache;

    public static function setUpBeforeClass()
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
     * Loads YAML files from the spec and converts them to PHPisms.
     *
     * @param string $name
     *
     * @return array
     */
    protected function loadSpec($name)
    {
        $filename = dirname(__FILE__) . '/../../../vendor/spec/specs/' . $name . '.yml';
        if (!file_exists($filename)) {
            return array();
        }

        $data = array();
        $yaml = new sfYamlParser();
        $file = file_get_contents($filename);

        // @hack: pre-process the 'lambdas' spec so the Symfony YAML parser doesn't complain.
        if ($name === '~lambdas') {
            $file = str_replace(" !code\n", "\n", $file);
        }

        $spec = $yaml->parse($file);

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
