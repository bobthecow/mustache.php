<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group functional
 * @group partials
 */
class Mustache_Test_Functional_NestedPartialIndentTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider partialsAndStuff
     */
    public function testNestedPartialsAreIndentedProperly($src, array $partials, $expected)
    {
        $m = new Mustache_Engine(array(
            'partials' => $partials
        ));
        $tpl = $m->loadTemplate($src);
        $this->assertEquals($expected, $tpl->render());
    }

    public function partialsAndStuff()
    {
        $partials = array(
            'a' => ' {{> b }}',
            'b' => ' {{> d }}',
            'c' => ' {{> d }}{{> d }}',
            'd' => 'D!',
        );

        return array(
            array(' {{> a }}', $partials, '   D!'),
            array(' {{> b }}', $partials, '  D!'),
            array(' {{> c }}', $partials, '  D!D!'),
        );
    }
}
