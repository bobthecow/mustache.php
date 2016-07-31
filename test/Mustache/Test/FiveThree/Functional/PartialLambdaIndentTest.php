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
 * @group lambdas
 * @group functional
 */
class Mustache_Test_FiveThree_Functional_PartialLambdaIndentTest extends PHPUnit_Framework_TestCase
{
    public function testLambdasInsidePartialsAreIndentedProperly()
    {
        $src = <<<'EOS'
<fieldset>
  {{> input }}
</fieldset>

EOS;
        $partial = <<<'EOS'
<input placeholder="{{# _t }}Enter your name{{/ _t }}">

EOS;

        $expected = <<<'EOS'
<fieldset>
  <input placeholder="ENTER YOUR NAME">
</fieldset>

EOS;

        $m = new Mustache_Engine(array(
            'partials' => array('input' => $partial),
        ));

        $tpl = $m->loadTemplate($src);

        $data = new Mustache_Test_FiveThree_Functional_ClassWithLambda();
        $this->assertEquals($expected, $tpl->render($data));
    }

    public function testLambdaInterpolationsInsidePartialsAreIndentedProperly()
    {
        $src = <<<'EOS'
<fieldset>
  {{> input }}
</fieldset>

EOS;
        $partial = <<<'EOS'
<input placeholder="{{ placeholder }}">

EOS;

        $expected = <<<'EOS'
<fieldset>
  <input placeholder="Enter your name">
</fieldset>

EOS;

        $m = new Mustache_Engine(array(
            'partials' => array('input' => $partial),
        ));

        $tpl = $m->loadTemplate($src);

        $data = new Mustache_Test_FiveThree_Functional_ClassWithLambda();
        $this->assertEquals($expected, $tpl->render($data));
    }
}

class Mustache_Test_FiveThree_Functional_ClassWithLambda
{
    public function _t()
    {
        return function ($val) {
            return strtoupper($val);
        };
    }

    public function placeholder()
    {
        return function () {
            return 'Enter your name';
        };
    }
}
