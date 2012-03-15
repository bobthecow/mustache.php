<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache;

use Mustache\Loader\ArrayLoader;
use Mustache\Loader\MutableLoader;
use Mustache\Loader\StringLoader;

/**
 * A Mustache implementation in PHP.
 *
 * {@link http://defunkt.github.com/mustache}
 *
 * Mustache is a framework-agnostic logic-less templating language. It enforces separation of view
 * logic from template files. In fact, it is not even possible to embed logic in the template.
 *
 * This is very, very rad.
 *
 * @author Justin Hileman {@link http://justinhileman.com}
 */
class Mustache {
	const VERSION      = '2.0.0-dev';
	const SPEC_VERSION = '1.1.2';

	// Template cache
	private $templates = array();

	// Environment
	private $templateClassPrefix = '__Mustache_';
	private $cache = null;
	private $loader;
	private $partialsLoader;
	private $helpers;
	private $charset = 'UTF-8';

	/**
	 * Mustache class constructor.
	 *
	 * Passing an $options array allows overriding certain Mustache options during instantiation:
	 *
	 *     $options = array(
	 *         // The class prefix for compiled templates. Defaults to '__Mustache_'
	 *         'template_class_prefix' => '\My\Namespace\Template\',
	 *
	 *         // A cache directory for compiled templates. Mustache will not cache templates unless this is set
	 *         'cache' => __DIR__.'/tmp/cache/mustache',
	 *
	 *         // A Mustache template loader instance. Uses a StringLoader if not specified
	 *         'loader' => new \Mustache\Loader\FilesystemLoader(__DIR__.'/views'),
	 *
	 *         // A Mustache loader instance for partials.
	 *         'partials_loader' => new \Mustache\Loader\FilesystemLoader(__DIR__.'/views/partials'),
	 *
	 *         // An array of Mustache partials. Useful for quick-and-dirty string template loading, but not as
	 *         // efficient or lazy as a Filesystem (or database) loader.
	 *         'partials' => array('foo' => file_get_contents(__DIR__.'/views/partials/foo.mustache')),
	 *
	 *         // An array of 'helpers'. Helpers can be global variables or objects, closures (e.g. for higher order
	 *         // sections), or any other valid Mustache context value. They will be prepended to the context stack,
	 *         // so they will be available in any template loaded by this Mustache instance.
	 *         'helpers' => array('i18n' => function($text) {
	 *              // do something translatey here...
	 *          }),
	 *
	 *         // character set for `htmlspecialchars`. Defaults to 'UTF-8'
	 *         'charset' => 'ISO-8859-1',
	 *     );
	 *
	 * @param array $options (default: array())
	 */
	public function __construct(array $options = array()) {
		if (isset($options['template_class_prefix'])) {
			$this->templateClassPrefix = $options['template_class_prefix'];
		}

		if (isset($options['cache'])) {
			$this->cache = $options['cache'];
		}

		if (isset($options['loader'])) {
			$this->setLoader($options['loader']);
		}

		if (isset($options['partials_loader'])) {
			$this->setPartialsLoader($options['partials_loader']);
		}

		if (isset($options['partials'])) {
			$this->setPartials($options['partials']);
		}

		if (isset($options['helpers'])) {
			$this->setHelpers($options['helpers']);
		}

		if (isset($options['charset'])) {
			$this->charset = $options['charset'];
		}
	}

	/**
	 * Shortcut 'render' invocation.
	 *
	 * Equivalent to calling `$mustache->loadTemplate($template)->render($data);`
	 *
	 * @see \Mustache\Mustache::loadTemplate
	 * @see \Mustache\Template::render
	 *
	 * @param string $template
	 * @param mixed  $data
	 *
	 * @return string Rendered template
	 */
	public function render($template, $data) {
		return $this->loadTemplate($template)->render($data);
	}

	/**
	 * Get the current Mustache character set.
	 *
	 * @return string
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * Set the Mustache template Loader instance.
	 *
	 * @param \Mustache\Loader $loader
	 */
	public function setLoader(Loader $loader) {
		$this->loader = $loader;
	}

	/**
	 * Get the current Mustache template Loader instance.
	 *
	 * If no Loader instance has been explicitly specified, this method will instantiate and return
	 * a StringLoader instance.
	 *
	 * @return \Mustache\Loader
	 */
	public function getLoader() {
		if (!isset($this->loader)) {
			$this->loader = new StringLoader;
		}

		return $this->loader;
	}

	/**
	 * Set the Mustache partials Loader instance.
	 *
	 * @param \Mustache\Loader $partialsLoader
	 */
	public function setPartialsLoader(Loader $partialsLoader) {
		$this->partialsLoader = $partialsLoader;
	}

	/**
	 * Get the current Mustache partials Loader instance.
	 *
	 * If no Loader instance has been explicitly specified, this method will instantiate and return
	 * an ArrayLoader instance.
	 *
	 * @return \Mustache\Loader
	 */
	public function getPartialsLoader() {
		if (!isset($this->partialsLoader)) {
			$this->partialsLoader = new ArrayLoader;
		}

		return $this->partialsLoader;
	}

	/**
	 * Set partials for the current partials Loader instance.
	 *
	 * @throws \RuntimeException If the current Loader instance is immutable
	 *
	 * @param array $partials (default: array())
	 */
	public function setPartials(array $partials = array()) {
		$loader = $this->getPartialsLoader();
		if (!$loader instanceof MutableLoader) {
			throw new \RuntimeException('Unable to set partials on an immutable Mustache Loader instance');
		}

		$loader->setTemplates($partials);
	}

	/**
	 * Set an array of Mustache helpers.
	 *
	 * An array of 'helpers'. Helpers can be global variables or objects, closures (e.g. for higher order sections), or
	 * any other valid Mustache context value. They will be prepended to the context stack, so they will be available in
	 * any template loaded by this Mustache instance.
	 *
	 * @throws \InvalidArgumentException if $helpers is not an array or \Traversable
	 *
	 * @param array|Traversable $helpers
	 */
	public function setHelpers($helpers) {
		if (!is_array($helpers) && !$helpers instanceof \Traversable) {
			throw new \InvalidArgumentException('setHelpers expects an array of helpers');
		}

		$this->getHelpers()->clear();

		foreach ($helpers as $name => $helper) {
			$this->addHelper($name, $helper);
		}
	}

	/**
	 * Get the current set of Mustache helpers.
	 *
	 * @see \Mustache\Mustache::setHelpers
	 *
	 * @return \Mustache\HelperCollection
	 */
	public function getHelpers() {
		if (!isset($this->helpers)) {
			$this->helpers = new HelperCollection;
		}

		return $this->helpers;
	}

	/**
	 * Add a new Mustache helper.
	 *
	 * @see \Mustache\Mustache::setHelpers
	 *
	 * @param string $name
	 * @param mixed  $helper
	 */
	public function addHelper($name, $helper) {
		$this->getHelpers()->add($name, $helper);
	}

	/**
	 * Get a Mustache helper by name.
	 *
	 * @see \Mustache\Mustache::setHelpers
	 *
	 * @param string $name
	 *
	 * @return mixed Helper
	 */
	public function getHelper($name) {
		return $this->getHelpers()->get($name);
	}

	/**
	 * Check whether this Mustache instance has a helper.
	 *
	 * @see \Mustache\Mustache::setHelpers
	 *
	 * @param string $name
	 *
	 * @return boolean True if the helper is present
	 */
	public function hasHelper($name) {
		return $this->getHelpers()->has($name);
	}

	/**
	 * Remove a helper by name.
	 *
	 * @see \Mustache\Mustache::setHelpers
	 *
	 * @param string $name
	 */
	public function removeHelper($name) {
		$this->getHelpers()->remove($name);
	}

	/**
	 * Set the Mustache Tokenizer instance.
	 *
	 * @param \Mustache\Tokenizer $tokenizer
	 */
	public function setTokenizer(Tokenizer $tokenizer) {
		$this->tokenizer = $tokenizer;
	}

	/**
	 * Get the current Mustache Tokenizer instance.
	 *
	 * If no Tokenizer instance has been explicitly specified, this method will instantiate and return a new one.
	 *
	 * @return \Mustache\Tokenizer
	 */
	public function getTokenizer() {
		if (!isset($this->tokenizer)) {
			$this->tokenizer = new Tokenizer;
		}

		return $this->tokenizer;
	}

	/**
	 * Set the Mustache Parser instance.
	 *
	 * @param \Mustache\Parser $parser
	 */
	public function setParser(Parser $parser) {
		$this->parser = $parser;
	}

	/**
	 * Get the current Mustache Parser instance.
	 *
	 * If no Parser instance has been explicitly specified, this method will instantiate and return a new one.
	 *
	 * @return \Mustache\Parser
	 */
	public function getParser() {
		if (!isset($this->parser)) {
			$this->parser = new Parser;
		}

		return $this->parser;
	}

	/**
	 * Set the Mustache Compiler instance.
	 *
	 * @param \Mustache\Compiler $compiler
	 */
	public function setCompiler(Compiler $compiler) {
		$this->compiler = $compiler;
	}

	/**
	 * Get the current Mustache Compiler instance.
	 *
	 * If no Compiler instance has been explicitly specified, this method will instantiate and return a new one.
	 *
	 * @return \Mustache\Compiler
	 */
	public function getCompiler() {
		if (!isset($this->compiler)) {
			$this->compiler = new Compiler;
		}

		return $this->compiler;
	}

	/**
	 * Helper method to generate a Mustache template class.
	 *
	 * @param string $source
	 *
	 * @return string Mustache Template class name
	 */
	public function getTemplateClassName($source) {
		return $this->templateClassPrefix . md5(self::VERSION . ':' . $source);
	}

	/**
	 * Load a Mustache Template by name.
	 *
	 * @param string $name
	 *
	 * @return \Mustache\Template
	 */
	public function loadTemplate($name) {
		return $this->loadSource($this->getLoader()->load($name));
	}

	/**
	 * Load a Mustache partial Template by name.
	 *
	 * This is a helper method used internally by Template instances for loading partial templates. You can most likely
	 * ignore it completely.
	 *
	 * @param string $name
	 *
	 * @return \Mustache\Template
	 */
	public function loadPartial($name) {
		return $this->loadSource($this->getPartialsLoader()->load($name));
	}

	/**
	 * Load a Mustache lambda Template by source.
	 *
	 * This is a helper method used by Template instances to generate subtemplates for Lambda sections. You can most
	 * likely ignore it completely.
	 *
	 * @param string $source
	 * @param string $delims (default: null)
	 *
	 * @return \Mustache\Template
	 */
	public function loadLambda($source, $delims = null) {
		if ($delims !== null) {
			$source = $delims . "\n" . $source;
		}

		return $this->loadSource($source);
	}

	/**
	 * Instantiate and return a Mustache Template instance by source.
	 *
	 * @see \Mustache\Mustache::loadTemplate
	 * @see \Mustache\Mustache::loadPartial
	 * @see \Mustache\Mustache::loadLambda
	 *
	 * @param string $source
	 *
	 * @return \Mustache\Template
	 */
	private function loadSource($source) {
		$className = $this->getTemplateClassName($source);

		if (!isset($this->templates[$className])) {
			if (!class_exists($className, false)) {
				if ($fileName = $this->getCacheFilename($source)) {
					if (!is_file($fileName)) {
						$this->writeCacheFile($fileName, $this->compile($source));
					}

					require_once $fileName;
				} else {
					eval('?>'.$this->compile($source));
				}
			}

			$this->templates[$className] = new $className($this);
		}

		return $this->templates[$className];
	}

	/**
	 * Helper method to tokenize a Mustache template.
	 *
	 * @see \Mustache\Tokenizer::scan
	 *
	 * @param string $source
	 *
	 * @return array Tokens
	 */
	private function tokenize($source) {
		return $this->getTokenizer()->scan($source);
	}

	/**
	 * Helper method to parse a Mustache template.
	 *
	 * @see \Mustache\Parser::parse
	 *
	 * @param string $source
	 *
	 * @return array Token tree
	 */
	private function parse($source) {
		return $this->getParser()->parse($this->tokenize($source));
	}

	/**
	 * Helper method to compile a Mustache template.
	 *
	 * @see \Mustache\Compiler::compile
	 *
	 * @param string $source
	 *
	 * @return string generated Mustache template class code
	 */
	private function compile($source) {
		return $this->getCompiler()->compile($source, $this->parse($source), $this->getTemplateClassName($source));
	}

	/**
	 * Helper method to generate a Mustache Template class cache filename.
	 *
	 * @param string $source
	 *
	 * @return string Mustache Template class cache filename
	 */
	private function getCacheFilename($source) {
		if ($this->cache) {
			return sprintf('%s/%s.php', $this->cache, $this->getTemplateClassName($source));
		}
	}

	/**
	 * Helper method to dump a generated Mustache Template subclass to the file cache.
	 *
	 * @throws \RuntimeException if unable to write to $fileName.
	 *
	 * @param string $fileName
	 * @param string $source
	 */
	private function writeCacheFile($fileName, $source) {
		if (!is_dir(dirname($fileName))) {
			mkdir(dirname($fileName), 0777, true);
		}

		$tempFile = tempnam(dirname($fileName), basename($fileName));
		if (false !== @file_put_contents($tempFile, $source)) {
			if (@rename($tempFile, $fileName)) {
				chmod($fileName, 0644);

				return;
			}
		}

		throw new \RuntimeException(sprintf('Failed to write cache file "%s".', $fileName));
	}
}
