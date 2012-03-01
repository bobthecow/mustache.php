<?php

namespace Mustache;

/**
 * Abstract Mustache Template class.
 *
 * @abstract
 */
abstract class Template {

	/**
	 * @var \Mustache\Mustache
	 */
	protected $mustache;

	/**
	 * Mustache Template constructor.
	 *
	 * @param \Mustache\Mustache $mustache
	 */
	public function __construct(Mustache $mustache) {
		$this->mustache = $mustache;
	}

	/**
	 * Mustache Template instances can be treated as a function and rendered by simply calling them:
	 *
	 *     $m = new Mustache;
	 *     $tpl = $m->loadTemplate('Hello, {{ name }}!');
	 *     echo $tpl(array('name' => 'World')); // "Hello, World!"
	 *
	 * @see \Mustache\Template::render
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
		return $this->renderInternal(new Context($context));
	}

	/**
	 * Internal rendering method implemented by Mustache Template concrete subclasses.
	 *
	 * This is where the magic happens :)
	 *
	 * @abstract
	 *
	 * @param \Mustache\Context $context
	 *
	 * @return string Rendered template
	 */
	abstract public function renderInternal(Context $context);
}
