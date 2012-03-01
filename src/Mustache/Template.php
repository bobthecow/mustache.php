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
 * Abstract Mustache Template class.
 *
 * @abstract
 */
abstract class Mustache_Template {

	/**
	 * @var Mustache_Mustache
	 */
	protected $mustache;

	/**
	 * Mustache Template constructor.
	 *
	 * @param Mustache_Mustache $mustache
	 */
	public function __construct(Mustache_Mustache $mustache) {
		$this->mustache = $mustache;
	}

	/**
	 * Mustache Template instances can be treated as a function and rendered by simply calling them:
	 *
	 *     $m = new Mustache_Mustache;
	 *     $tpl = $m->loadTemplate('Hello, {{ name }}!');
	 *     echo $tpl(array('name' => 'World')); // "Hello, World!"
	 *
	 * @see Mustache_Template::render
	 *
	 * @param mixed $context Array or object rendering context (default: array())
	 *
	 * @return string Rendered template
	 */
	public function __invoke($context = array()) {
		return $this->render($context);
	}

	/**
	 * Render this template given the rendering context.
	 *
	 * @param mixed $context Array or object rendering context (default: array())
	 *
	 * @return string Rendered template
	 */
	public function render($context = array()) {
		return $this->renderInternal(new Mustache_Context($context));
	}

	/**
	 * Internal rendering method implemented by Mustache Template concrete subclasses.
	 *
	 * This is where the magic happens :)
	 *
	 * @abstract
	 *
	 * @param Mustache_Context $context
	 *
	 * @return string Rendered template
	 */
	abstract public function renderInternal(Mustache_Context $context);
}
