<?php
namespace Mustache\Test\FiveThree\Functional;

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group lambdas
 * @group functional
 */
class LambdaHelperTest extends \PHPUnit_Framework_TestCase
{
    private $mustache;

    public function setUp()
    {
        $this->mustache = new \Mustache\Engine;
    }

    public function testSectionLambdaHelper()
    {
        $one = $this->mustache->loadTemplate('{{name}}');
        $two = $this->mustache->loadTemplate('{{#lambda}}{{name}}{{/lambda}}');

        $foo = new \StdClass;
        $foo->name = 'Mario';
        $foo->lambda = function ($text, $mustache) {
            return strtoupper($mustache->render($text));
        };

        $this->assertEquals('Mario', $one->render($foo));
        $this->assertEquals('MARIO', $two->render($foo));
    }
}
