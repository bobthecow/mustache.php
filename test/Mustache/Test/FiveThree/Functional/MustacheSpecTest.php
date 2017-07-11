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
 * A PHPUnit test case wrapping the Mustache Spec.
 *
 * @group mustache-spec
 * @group functional
 */
class Mustache_Test_FiveThree_Functional_MustacheSpecTest extends Mustache_Test_SpecTestCase
{
    /**
     * For some reason data providers can't mark tests skipped, so this test exists
     * simply to provide a 'skipped' test if the `spec` submodule isn't initialized.
     */
    public function testSpecInitialized()
    {
        if (!file_exists(dirname(__FILE__) . '/../../../../../vendor/spec/specs/')) {
            $this->markTestSkipped('Mustache spec submodule not initialized: run "git submodule update --init"');
        }
    }

    /**
     * @group lambdas
     * @dataProvider loadLambdasSpec
     */
    public function testLambdasSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template($this->prepareLambdasSpec($data)), $desc);
    }

    public function loadLambdasSpec()
    {
        return $this->loadSpec('~lambdas');
    }

    /**
     * Extract and lambdafy any 'lambda' values found in the $data array.
     */
    private function prepareLambdasSpec($data)
    {
        foreach ($data as $key => $val) {
            if ($key === 'lambda') {
                if (!isset($val['php'])) {
                    $this->markTestSkipped(sprintf('PHP lambda test not implemented for this test.'));
                }

                $func = $val['php'];
                $data[$key] = function ($text = null) use ($func) {
                    return eval($func);
                };
            } elseif (is_array($val)) {
                $data[$key] = $this->prepareLambdasSpec($val);
            }
        }

        return $data;
    }
}
