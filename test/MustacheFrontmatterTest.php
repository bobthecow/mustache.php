<?php

require_once '../Mustache.php';
require_once './lib/yaml/lib/sfYamlParser.php';

class MustacheFrontmatterTest extends PHPUnit_Framework_TestCase {

	public function testFrontmatter() {
		$template = <<<MUSTACHE_IN
---
name: chris
---
  Hi {{name}}!
MUSTACHE_IN;

		if (!function_exists('yaml_parse')) {
			$this->markTestSkipped('Unable to test Mustache frontmatter without yaml_parse()');
			return;
		}

		$mustache = new Mustache;

		$this->assertEquals("  Hi chris!", $mustache->render($template));
	}

	public function testNoFrontmatter() {
		$template = <<<MUSTACHE_IN
  Hi {{name}}!
MUSTACHE_IN;

		$mustache = new Mustache;

		$this->assertEquals("  Hi !", $mustache->render($template));
	}

	public function testFrontmatterOverride() {
		$template = <<<MUSTACHE_IN
---
name: chris
---
  Hi {{name}}!
MUSTACHE_IN;

		$data = array('name' => 'mark');

		if (!function_exists('yaml_parse')) {
			$this->markTestSkipped('Unable to test Mustache frontmatter without yaml_parse()');
			return;
		}

		$mustache = new Mustache;

		$this->assertEquals("  Hi mark!", $mustache->render($template, $data));
	}

	public function testFrontmatterDepthAndSections() {
		$template = <<<MUSTACHE_IN
---
names: [ {name: chris}, {name: mark}, {name: scott} ]
---
{{#names}}
  Hi {{name}}!
{{/names}}
MUSTACHE_IN;

		$output = <<<MUSTACHE_OUT
  Hi chris!
  Hi mark!
  Hi scott!

MUSTACHE_OUT;

		if (!function_exists('yaml_parse')) {
			$this->markTestSkipped('Unable to test Mustache frontmatter without yaml_parse()');
			return;
		}

		$mustache = new Mustache;

		$this->assertEquals($output, $mustache->render($template));
	}

	public function testFrontmatterRepeat() {
		$template = <<<MUSTACHE_IN
---
greeting: Hello
name: Chris
---
greeting: Goodbye
name: Mark
---
greeting: Whatever
name: Scott
---
  {{ greeting }} {{name}}!
MUSTACHE_IN;

		$output = <<<MUSTACHE_OUT
  Hello Chris!
  Goodbye Mark!
  Whatever Scott!
MUSTACHE_OUT;

		if (!function_exists('yaml_parse')) {
			$this->markTestSkipped('Unable to test Mustache frontmatter without yaml_parse()');
			return;
		}

		$mustache = new Mustache;

		$this->assertEquals($output, $mustache->render($template));
	}

	public function testFrontmatterYamlNamespace() {
		$template = <<<MUSTACHE_IN
---
title: Hello World
---
<h1>{{ page.title }}</h1>
MUSTACHE_IN;

		if (!function_exists('yaml_parse')) {
			$this->markTestSkipped('Unable to test Mustache frontmatter without yaml_parse()');
			return;
		}

		$mustache = new Mustache($template, null, null, array(
			'yaml_namespace' => 'page'
		));

		$this->assertEquals("<h1>Hello World</h1>", $mustache->render());
	}

	public function testFrontmatterYamlNamespaceOverride() {
		$template = <<<MUSTACHE_IN
---
title: Hello World
---
<h1>{{ page.title }}</h1>
MUSTACHE_IN;

		if (!function_exists('yaml_parse')) {
			$this->markTestSkipped('Unable to test Mustache frontmatter without yaml_parse()');
			return;
		}

		$mustache = new Mustache($template, array('page' => array('title' => 'Goodnight Moon')), null, array(
			'yaml_namespace' => 'page'
		));

		$this->assertEquals("<h1>Goodnight Moon</h1>", $mustache->render());
	}

	public function testFrontmatterAlternativeYamlParser() {
		$template = <<<MUSTACHE_IN
---
whatever: working
---
Alternative YAML parser is {{ whatever }}
MUSTACHE_IN;

		$yaml = new sfYamlParser();

		$mustache = new Mustache($template, null, null, array(
			'yaml_parser' => array($yaml, 'parse')
		));

		$this->assertEquals("Alternative YAML parser is working", $mustache->render());
	}

	public function testFrontmatterCustomYamlParser() {
		$template = <<<MUSTACHE_IN
---
placebo: true
---
Custom YAML parser is {{ whatever }}
MUSTACHE_IN;

		$mustache = new Mustache($template, null, null, array(
			'yaml_parser' => array($this, 'bogusYamlParser')
		));

		$this->assertEquals("Custom YAML parser is working", $mustache->render());
	}

	public function bogusYamlParser($yaml) {
		return array('whatever' => 'working');
	}

}