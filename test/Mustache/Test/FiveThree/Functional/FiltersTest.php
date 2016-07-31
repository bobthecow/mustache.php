<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2016 Justin Hileman
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
        $this->mustache = new Mustache_Engine();
    }

    /**
     * @dataProvider singleFilterData
     */
    public function testSingleFilter($tpl, $helpers, $data, $expect)
    {
        $this->mustache->setHelpers($helpers);
        $this->assertEquals($expect, $this->mustache->render($tpl, $data));
    }

    public function singleFilterData()
    {
        $helpers = array(
            'longdate' => function (\DateTime $value) {
                return $value->format('Y-m-d h:m:s');
            },
            'echo' => function ($value) {
                return array($value, $value, $value);
            },
        );

        return array(
            array(
                '{{% FILTERS }}{{ date | longdate }}',
                $helpers,
                (object) array('date' => new DateTime('1/1/2000', new DateTimeZone('UTC'))),
                '2000-01-01 12:01:00',
            ),

            array(
                '{{% FILTERS }}{{# word | echo }}{{ . }}!{{/ word | echo }}',
                $helpers,
                array('word' => 'bacon'),
                'bacon!bacon!bacon!',
            ),
        );
    }

    public function testChainedFilters()
    {
        $tpl = $this->mustache->loadTemplate('{{% FILTERS }}{{ date | longdate | withbrackets }}');

        $this->mustache->addHelper('longdate', function (\DateTime $value) {
            return $value->format('Y-m-d h:m:s');
        });

        $this->mustache->addHelper('withbrackets', function ($value) {
            return sprintf('[[%s]]', $value);
        });

        $foo = new \StdClass();
        $foo->date = new DateTime('1/1/2000', new DateTimeZone('UTC'));

        $this->assertEquals('[[2000-01-01 12:01:00]]', $tpl->render($foo));
    }

    const CHAINED_SECTION_FILTERS_TPL = <<<'EOS'
{{% FILTERS }}
{{# word | echo | with_index }}
{{ key }}: {{ value }}
{{/ word | echo | with_index }}
EOS;

    public function testChainedSectionFilters()
    {
        $tpl = $this->mustache->loadTemplate(self::CHAINED_SECTION_FILTERS_TPL);

        $this->mustache->addHelper('echo', function ($value) {
            return array($value, $value, $value);
        });

        $this->mustache->addHelper('with_index', function ($value) {
            return array_map(function ($k, $v) {
                return array(
                    'key'   => $k,
                    'value' => $v,
                );
            }, array_keys($value), $value);
        });

        $this->assertEquals("0: bacon\n1: bacon\n2: bacon\n", $tpl->render(array('word' => 'bacon')));
    }

    /**
     * @dataProvider interpolateFirstData
     */
    public function testInterpolateFirst($tpl, $data, $expect)
    {
        $this->assertEquals($expect, $this->mustache->render($tpl, $data));
    }

    public function interpolateFirstData()
    {
        $data = array(
            'foo' => 'FOO',
            'bar' => function ($value) {
                return ($value === 'FOO') ? 'win!' : 'fail :(';
            },
        );

        return array(
            array('{{% FILTERS }}{{ foo | bar }}',                         $data, 'win!'),
            array('{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}', $data, 'win!'),
        );
    }

    /**
     * @expectedException Mustache_Exception_UnknownFilterException
     * @dataProvider brokenPipeData
     */
    public function testThrowsExceptionForBrokenPipes($tpl, $data)
    {
        $this->mustache->render($tpl, $data);
    }

    public function brokenPipeData()
    {
        return array(
            array('{{% FILTERS }}{{ foo | bar }}',       array()),
            array('{{% FILTERS }}{{ foo | bar }}',       array('foo' => 'FOO')),
            array('{{% FILTERS }}{{ foo | bar }}',       array('foo' => 'FOO', 'bar' => 'BAR')),
            array('{{% FILTERS }}{{ foo | bar }}',       array('foo' => 'FOO', 'bar' => array(1, 2))),
            array('{{% FILTERS }}{{ foo | bar | baz }}', array('foo' => 'FOO', 'bar' => function () {
                return 'BAR';
            })),
            array('{{% FILTERS }}{{ foo | bar | baz }}', array('foo' => 'FOO', 'baz' => function () {
                return 'BAZ';
            })),
            array('{{% FILTERS }}{{ foo | bar | baz }}', array('bar' => function () {
                return 'BAR';
            })),
            array('{{% FILTERS }}{{ foo | bar | baz }}', array('baz' => function () {
                return 'BAZ';
            })),
            array('{{% FILTERS }}{{ foo | bar.baz }}',   array('foo' => 'FOO', 'bar' => function () {
                return 'BAR';
            }, 'baz' => function () {
                return 'BAZ';
            })),

            array('{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             array()),
            array('{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             array('foo' => 'FOO')),
            array('{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             array('foo' => 'FOO', 'bar' => 'BAR')),
            array('{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             array('foo' => 'FOO', 'bar' => array(1, 2))),
            array('{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}', array('foo' => 'FOO', 'bar' => function () {
                return 'BAR';
            })),
            array('{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}', array('foo' => 'FOO', 'baz' => function () {
                return 'BAZ';
            })),
            array('{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}', array('bar' => function () {
                return 'BAR';
            })),
            array('{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}', array('baz' => function () {
                return 'BAZ';
            })),
            array('{{% FILTERS }}{{# foo | bar.baz }}{{ . }}{{/ foo | bar.baz }}',     array('foo' => 'FOO', 'bar' => function () {
                return 'BAR';
            }, 'baz' => function () {
                return 'BAZ';
            })),
        );
    }
}
