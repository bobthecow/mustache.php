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
 * @group filters
 * @group functional
 */
class Mustache_Test_FiveThree_Functional_SectionFiltersTest extends PHPUnit_Framework_TestCase
{

    private $mustache;

    public function setUp()
    {
        $this->mustache = new Mustache_Engine;
    }

    public function testSingleFilter()
    {
        $tpl = $this->mustache->loadTemplate('{{% FILTERS }}{{# word | echo }}{{ . }}!{{/ word | echo }}');

        $this->mustache->addHelper('echo', function($value) {
            return array($value, $value, $value);
        });

        $this->assertEquals('bacon!bacon!bacon!', $tpl->render(array('word' => 'bacon')));
    }

    const CHAINED_FILTERS_TPL = <<<EOS
{{% FILTERS }}
{{# word | echo | with_index }}
{{ key }}: {{ value }}
{{/ word | echo | with_index }}
EOS;

    public function testChainedFilters()
    {
        $tpl = $this->mustache->loadTemplate(self::CHAINED_FILTERS_TPL);

        $this->mustache->addHelper('echo', function($value) {
            return array($value, $value, $value);
        });

        $this->mustache->addHelper('with_index', function($value) {
            return array_map(function($k, $v) {
                return array(
                    'key'   => $k,
                    'value' => $v,
                );
            }, array_keys($value), $value);
        });

        $this->assertEquals("0: bacon\n1: bacon\n2: bacon\n", $tpl->render(array('word' => 'bacon')));
    }

    public function testInterpolateFirst()
    {
        $tpl = $this->mustache->loadTemplate('{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}');
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
            ->loadTemplate(sprintf('{{%% FILTERS }}{{# %s }}{{ . }}{{/ %s }}', $tpl, $tpl))
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
