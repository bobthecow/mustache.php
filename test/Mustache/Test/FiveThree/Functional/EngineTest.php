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
 * @group pragmas
 * @group functional
 */
class Mustache_Test_FiveThree_Functional_EngineTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider pragmaData
     */
    public function testPragmasConstructorOption($pragmas, $helpers, $data, $tpl, $expect)
    {
        $mustache = new Mustache_Engine(array(
            'pragmas' => $pragmas,
            'helpers' => $helpers,
        ));

        $this->assertEquals($expect, $mustache->render($tpl, $data));
    }

    public function pragmaData()
    {
        $helpers = array(
            'longdate' => function (\DateTime $value) {
                return $value->format('Y-m-d h:m:s');
            },
        );

        $data = array(
            'date' => new DateTime('1/1/2000', new DateTimeZone('UTC')),
        );

        $tpl = '{{ date | longdate }}';

        return array(
            array(array(Mustache_Engine::PRAGMA_FILTERS), $helpers, $data, $tpl, '2000-01-01 12:01:00'),
            array(array(),                                $helpers, $data, $tpl, ''),
        );
    }
}
