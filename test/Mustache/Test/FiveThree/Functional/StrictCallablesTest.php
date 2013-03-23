<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2013 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group lambdas
 * @group functional
 */
class Mustache_Test_FiveThree_Functional_StrictCallablesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider callables
     */
    public function testStrictCallablesDisabled($name, $section, $expected)
    {
        $mustache = new Mustache_Engine(array('strict_callables' => false));
        $tpl      = $mustache->loadTemplate('{{# section }}{{ name }}{{/ section }}');

        $data = new StdClass;
        $data->name    = $name;
        $data->section = $section;

        $this->assertEquals($expected, $tpl->render($data));
    }

    public function callables()
    {
        $lambda = function($tpl, $mustache) {
            return strtoupper($mustache->render($tpl));
        };

        return array(
            // Interpolation lambdas
            array(
                array($this, 'instanceName'),
                $lambda,
                'YOSHI',
            ),
            array(
                array(__CLASS__, 'staticName'),
                $lambda,
                'YOSHI',
            ),
            array(
                function() { return 'Yoshi'; },
                $lambda,
                'YOSHI',
            ),

            // Section lambdas
            array(
                'Yoshi',
                array($this, 'instanceCallable'),
                'YOSHI',
            ),
            array(
                'Yoshi',
                array(__CLASS__, 'staticCallable'),
                'YOSHI',
            ),
            array(
                'Yoshi',
                $lambda,
                'YOSHI',
            ),
        );
    }


    /**
     * @group wip
     * @dataProvider strictCallables
     */
    public function testStrictCallablesEnabled($name, $section, $expected)
    {
        $mustache = new Mustache_Engine(array('strict_callables' => true));
        $tpl      = $mustache->loadTemplate('{{# section }}{{ name }}{{/ section }}');

        $data = new StdClass;
        $data->name    = $name;
        $data->section = $section;

        $this->assertEquals($expected, $tpl->render($data));
    }

    public function strictCallables()
    {
        $lambda = function($tpl, $mustache) {
            return strtoupper($mustache->render($tpl));
        };

        return array(
            // Interpolation lambdas
            array(
                function() { return 'Yoshi'; },
                $lambda,
                'YOSHI',
            ),

            // Section lambdas
            array(
                'Yoshi',
                array($this, 'instanceCallable'),
                'YoshiYoshi',
            ),
            array(
                'Yoshi',
                array(__CLASS__, 'staticCallable'),
                'YoshiYoshi',
            ),
            array(
                'Yoshi',
                function($tpl, $mustache) {
                    return strtoupper($mustache->render($tpl));
                },
                'YOSHI',
            ),
        );
    }

    public function instanceCallable($tpl, $mustache)
    {
        return strtoupper($mustache->render($tpl));
    }

    public static function staticCallable($tpl, $mustache)
    {
        return strtoupper($mustache->render($tpl));
    }

    public function instanceName()
    {
        return 'Yoshi';
    }

    public static function staticName()
    {
        return 'Yoshi';
    }
}
