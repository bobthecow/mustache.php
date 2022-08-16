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
 * @group lambdas
 * @group functional
 */
class Mustache_Test_FiveThree_Functional_DisableLambdaRenderingTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider callables
     */
    public function testDisableLambdaRendering($disable, $code, $expected)
    {
        $mustache = new Mustache_Engine(array('disable_lambda_rendering' => $disable));
        $tpl      = $mustache->loadTemplate($code);

        $data = new StdClass();
        $data->name   = 'Yoshi';
        $data->lambda = function ($tpl) {
            return $tpl;
        };

        $this->assertEquals($expected, $tpl->render($data));
    }

    public function callables()
    {
        return array(
            // Lambdas with rendering enabled
            array(
                false,
                '{{# lambda }}{{ name }}{{/ lambda }}',
                'Yoshi',
            ),
            array(
                false,
                '{{# lambda }}{{# lambda }}Test{{/ lambda }}{{/ lambda }}',
                'Test',
            ),

            // Lambdas with rendering disabled
            array(
                true,
                '{{# lambda }}{{ name }}{{/ lambda }}',
                '{{ name }}',
            ),
            array(
                true,
                '{{# lambda }}{{# lambda }}Test{{/ lambda }}{{/ lambda }}',
                '{{# lambda }}Test{{/ lambda }}',
            ),
        );
    }
}
