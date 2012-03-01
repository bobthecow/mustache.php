<?php

require_once '../Mustache.php';

/**
 * @group mustache_injection
 */
class MustacheInjectionSectionTest extends PHPUnit_Framework_TestCase {

    // interpolation

    public function testInterpolationInjection() {
        $data = array(
            'a' => '{{ b }}',
            'b' => 'FAIL'
        );
        $template = '{{ a }}';
        $output = '{{ b }}';
        $m = new Mustache();
        $this->assertEquals($output, $m->render($template, $data));
    }

    public function testUnescapedInterpolationInjection() {
        $data = array(
            'a' => '{{ b }}',
            'b' => 'FAIL'
        );
        $template = '{{{ a }}}';
        $output = '{{ b }}';
        $m = new Mustache();
        $this->assertEquals($output, $m->render($template, $data));
    }


    // sections

    public function testSectionInjection() {
        $data = array(
            'a' => true,
            'b' => '{{ c }}',
            'c' => 'FAIL'
        );
        $template = '{{# a }}{{ b }}{{/ a }}';
        $output = '{{ c }}';
        $m = new Mustache();
        $this->assertEquals($output, $m->render($template, $data));
    }

    public function testUnescapedSectionInjection() {
        $data = array(
            'a' => true,
            'b' => '{{ c }}',
            'c' => 'FAIL'
        );
        $template = '{{# a }}{{{ b }}}{{/ a }}';
        $output = '{{ c }}';
        $m = new Mustache();
        $this->assertEquals($output, $m->render($template, $data));
    }


    // partials

    public function testPartialInjection() {
        $data = array(
            'a' => '{{ b }}',
            'b' => 'FAIL'
        );
        $template = '{{> partial }}';
        $partials = array(
            'partial' => '{{ a }}',
        );
        $output = '{{ b }}';
        $m = new Mustache();
        $this->assertEquals($output, $m->render($template, $data, $partials));
    }

    public function testPartialUnescapedInjection() {
        $data = array(
            'a' => '{{ b }}',
            'b' => 'FAIL'
        );
        $template = '{{> partial }}';
        $partials = array(
            'partial' => '{{{ a }}}',
        );
        $output = '{{ b }}';
        $m = new Mustache();
        $this->assertEquals($output, $m->render($template, $data, $partials));
    }


    // lambdas

    public function testLambdaInterpolationInjection() {
        $data = array(
            'a' => array($this, 'interpolationLambda'),
            'b' => '{{ c }}',
            'c' => 'FAIL'
        );
        $template = '{{ a }}';
        $output = '{{ c }}';
        $m = new Mustache();
        $this->assertEquals($output, $m->render($template, $data));
    }

    public function interpolationLambda() {
        return '{{ b }}';
    }

    public function testLambdaSectionInjection() {
        $data = array(
            'a' => array($this, 'sectionLambda'),
            'b' => '{{ c }}',
            'c' => 'FAIL'
        );
        $template = '{{# a }}b{{/ a }}';
        $output = '{{ c }}';
        $m = new Mustache();
        $this->assertEquals($output, $m->render($template, $data));
    }

    public function sectionLambda($content) {
        return '{{ ' . $content . ' }}';
    }

}