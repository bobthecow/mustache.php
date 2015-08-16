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
 * @group functional
 */
class Mustache_Test_Functional_LambdaTest extends PHPUnit_Framework_TestCase
{

    public function testLambda()
    {
        $m = new Mustache_Engine;
        $tpl = $m->loadTemplate('{{#people}}{{name}} {{/people}}');

        $data = array('people' => function($text, Mustache_LambdaHelper $helper) {
            $people = array(
                array('name' => 'Alice'),
                array('name' => 'Bob'),
                array('name' => 'Charlie'),
            );

            $str = '';
            foreach ($people as $person)
                $str .= $helper->render($text, $person);
            return $str;
        });

        $this->assertEquals('Alice Bob Charlie ', $tpl->render($data));
    }
}
