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
class Mustache_Test_Functional_MustacheDynamicNamesSpecTest extends Mustache_Test_SpecTestCase
{
    public static function setUpBeforeClass()
    {
        self::$mustache = new Mustache_Engine(array(
          'pragmas' => array(Mustache_Engine::PRAGMA_DYNAMIC_NAMES),
        ));
    }

    /**
     * For some reason data providers can't mark tests skipped, so this test exists
     * simply to provide a 'skipped' test if the `spec` submodule isn't initialized.
     */
    public function testSpecInitialized()
    {
        if (!file_exists(dirname(__FILE__) . '/../../../../vendor/spec/specs/')) {
            $this->markTestSkipped('Mustache spec submodule not initialized: run "git submodule update --init"');
        }
    }

    /**
     * @group dynamic-names
     * @dataProvider loadDynamicNamesSpec
     */
    public function testDynamicNamesSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    public function loadDynamicNamesSpec()
    {
        return $this->loadSpec('~dynamic-names');
    }
}
