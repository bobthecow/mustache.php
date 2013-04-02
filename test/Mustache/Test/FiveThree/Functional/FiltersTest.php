<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group filters
 * @group functional
 */
class Mustache_Test_FiveThree_Functional_FiltersTest extends PHPUnit_Framework_TestCase
{

    private $mustache;

    public function setUp()
    {
        $this->mustache = new Mustache_Engine;
    }

    public function testSingleFilter()
    {
        $tpl = $this->mustache->loadTemplate('{{% FILTERS }}{{ date | longdate }}');

        $this->mustache->addHelper('longdate', function(\DateTime $value) {
            return $value->format('Y-m-d h:m:s');
        });

        $foo = new \StdClass;
        $foo->date = new DateTime('1/1/2000');

        $this->assertEquals('2000-01-01 12:01:00', $tpl->render($foo));
    }

    public function testChainedFilters()
    {
        $tpl = $this->mustache->loadTemplate('{{% FILTERS }}{{ date | longdate | withbrackets }}');

        $this->mustache->addHelper('longdate', function(\DateTime $value) {
            return $value->format('Y-m-d h:m:s');
        });

        $this->mustache->addHelper('withbrackets', function($value) {
            return sprintf('[[%s]]', $value);
        });

        $foo = new \StdClass;
        $foo->date = new DateTime('1/1/2000');

        $this->assertEquals('[[2000-01-01 12:01:00]]', $tpl->render($foo));
    }

    public function testInterpolateFirst()
    {
        $tpl = $this->mustache->loadTemplate('{{% FILTERS }}{{ foo | bar }}');
        $this->assertEquals('win!', $tpl->render(array(
            'foo' => 'FOO',
            'bar' => function($value) {
                return ($value === 'FOO') ? 'win!' : 'fail :(';
            },
        )));
    }

    /**
     * @expectedException Mustache_Exception_UnknownFilterException
     * @dataProvider getBrokenPipes
     */
    public function testThrowsExceptionForBrokenPipes($tpl, $data)
    {
        $this->mustache
            ->loadTemplate(sprintf('{{%% FILTERS }}{{ %s }}', $tpl))
                ->render($data);
    }

    public function getBrokenPipes()
    {
        return array(
            array('foo | bar', array()),
            array('foo | bar', array('foo' => 'FOO')),
            array('foo | bar', array('foo' => 'FOO', 'bar' => 'BAR')),
            array('foo | bar', array('foo' => 'FOO', 'bar' => array(1, 2))),
            array('foo | bar | baz', array('foo' => 'FOO', 'bar' => function() { return 'BAR'; })),
            array('foo | bar | baz', array('foo' => 'FOO', 'baz' => function() { return 'BAZ'; })),
            array('foo | bar | baz', array('bar' => function() { return 'BAR'; })),
            array('foo | bar | baz', array('baz' => function() { return 'BAZ'; })),
            array('foo | bar.baz', array('foo' => 'FOO', 'bar' => function() { return 'BAR'; }, 'baz' => function() { return 'BAZ'; })),
        );
    }
}
