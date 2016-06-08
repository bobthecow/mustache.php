<?php


/**
 * @group sections
 * @group functional
 */
class Mustache_Test_Wartman_Functional_SelfClosingTest extends PHPUnit_Framework_TestCase
{

    private $mustache;

    public function setUp()
    {
        $this->mustache = new Mustache_Engine([
            'pragmas' => [
                Mustache_Engine::PRAGMA_SELF_CLOSING,
                Mustache_Engine::PRAGMA_BLOCKS
            ],
        ]);
    }

    public function testHandlesSelfClosingSections()
    {
        $tpl = $this->mustache->loadTemplate('{{# foo /}}');
        $this->assertEquals('FOO', $tpl->render(array('foo' => function () { return 'FOO'; })));
    }

    public function testHandlesSelfClosingBlocks()
    {
        $this->mustache->setPartials([
            'foo' => '{{$ bar /}}'
        ]);
        $tpl = $this->mustache->loadTemplate('{{< foo }}{{/ foo }}');
        $this->assertEquals('', $tpl->render([]));
        $tpl = $this->mustache->loadTemplate('{{< foo }}{{$ bar }}bin{{/ bar }}{{/ foo }}');
        $this->assertEquals('bin', $tpl->render([]));
    }

}