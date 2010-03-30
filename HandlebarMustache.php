<?php

/**
 * HandlebarMustache class.
 *
 * This is an extended Mustache class which contains file handling for templates
 * and partial templates.
 *
 * @extends Mustache
 */
class HandlebarMustache extends Mustache {

	/**
	 * templateBase directory.
	 *
	 * If none is specified, this will default to `dirname(__FILE__)`.
	 *
	 * @var string
	 * @access protected
	 */
	protected $templateBase;

	/**
	 * templateName.
	 *
	 * If none is specified, this will default to an underscorified version of the class name.
	 *
	 * @var string
	 * @access protected
	 */
	protected $templateName;

	/**
	 * HandlebarMustache class constructor.
	 *
	 * @access public
	 * @param string $template (default: null)
	 * @param mixed $view (default: null)
	 * @param array $partials (default: null)
	 * @return void
	 */
	public function __construct($template = null, $view = null, $partials = null) {
		parent::__construct($template,$view,$partials);

		// default template base is the current directory.
		if (!isset($this->templateBase)) {
			$this->setTemplateBase(dirname(__FILE__));
		}

		// default template name is the underscorified class name.
		if (!isset($this->templateName)) {
			$this->templateName = strtolower(preg_replace('#(?<!^)([A-Z]+)#', '_\1', get_class($this)));
		}
	}

	/**
	 * Override the current templateBase.
	 *
	 * @access public
	 * @param string $dir
	 * @return void
	 */
	public function setTemplateBase($dir) {
		if (substr($dir, -1) !== '/') {
			$dir .= '/';
		}
		$this->templateBase = $dir;
	}

	/**
	 * Override the default templateName.
	 *
	 * @access public
	 * @param string $name
	 * @return void
	 */
	public function setTemplateName($name) {
		$this->templateName = $name;
	}

	/**
	 * Load a template file. This file will be relative to $this->templateBase.
	 * A '.mustache' file extension is assumed if none is provided in $file.
	 *
	 * @access public
	 * @param string $name
	 * @return void
	 */
	public function loadTemplate($name) {
		if (strpos($name, '.') === false) {
			$name .= '.mustache';
		}

		$filename = $this->templateBase . $name;
		if (file_exists($filename)) {
			$this->template = file_get_contents($filename);
		} else {
			$this->template = null;
		}
	}

	/**
	 * Load a partial, either from $this->partials or from a file in the templateBase
	 * directory.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @return string Partial template.
	 */
	protected function getPartial($tag_name) {
		try {
			if ($result = parent::getPartial($tag_name)) {
				return $result;
			}
		} catch (MustacheException $e) {
			// Ignore the UNKNOWN_PARTIAL exceptions, we'll just look for a template file.
			if ($e->getCode() !== MustacheException::UNKNOWN_PARTIAL) {
				throw $e;
			}
		}

		$filename = $this->templateBase . $tag_name . '.mustache';
		if (file_exists($filename)) {
			$this->partials[$tag_name] = file_get_contents($filename);
			return $this->partials[$tag_name];
		} else {
			if ($this->throwPartialExceptions) {
				throw new MustacheException(
					'Unknown partial: ' . $tag_name,
					MustacheException::UNKNOWN_PARTIAL
				);
			} else {
				return '';
			}
		}
	}

	/**
	 * Render the given template and view object.
	 *
	 * Defaults to the template and view passed to the class constructor unless a new one is provided.
	 * Optionally, pass an associative array of partials as well.
	 *
	 * @access public
	 * @param string $template (default: null)
	 * @param mixed $view (default: null)
	 * @param array $partials (default: null)
	 * @return string Rendered Mustache template.
	 */
	public function render($template = null, $view = null, $partials = null) {
		if ($template === null && !isset($this->template)) {
			$this->loadTemplate($this->templateName);
		}

		return parent::render($template, $view, $partials);
	}
}