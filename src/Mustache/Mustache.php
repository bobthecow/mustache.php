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
class Mustache_Mustache {
	const VERSION      = '2.0.0-dev';
	const SPEC_VERSION = '1.1.2';

	// Template cache
	private $templates = array();

	// Environment
	private $templateClassPrefix = '__Mustache_';
	private $cache = null;
	private $loader;
	private $partialsLoader;
	private $charset = 'UTF-8';

	/**
	 * Mustache class constructor.
	 *
	 * Passing an $options array allows overriding certain Mustache options during instantiation:
	 *
	 *     $options = array(
	 *         // The class prefix for compiled templates. Defaults to '__Mustache_'
	 *         'template_class_prefix' => '__MyTemplates_',
	 *
	 *         // A cache directory for compiled templates. Mustache will not cache templates unless this is set
	 *         'cache' => __DIR__.'/tmp/cache/mustache',
	 *
	 *         // A Mustache template loader instance. Uses a StringLoader if not specified
	 *         'loader' => new Mustache_Loader_FilesystemLoader(__DIR__.'/views'),
	 *
	 *         // A Mustache loader instance for partials.
	 *         'partials_loader' => new Mustache_Loader_FilesystemLoader(__DIR__.'/views/partials'),
	 *
	 *         // An array of Mustache partials. Useful for quick-and-dirty string template loading, but not as
	 *         // efficient or lazy as a Filesystem (or database) loader.
	 *         'partials' => array('foo' => file_get_contents(__DIR__.'/views/partials/foo.mustache')),
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

		if (isset($options['charset'])) {
			$this->charset = $options['charset'];
		}
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
	 * @param Mustache_Loader $loader
	 */
	public function setLoader(Mustache_Loader $loader) {
		$this->loader = $loader;
	}

	/**
	 * Get the current Mustache template Loader instance.
	 *
	 * If no Loader instance has been explicitly specified, this method will instantiate and return
	 * a StringLoader instance.
	 *
	 * @return Mustache_Loader
	 */
	public function getLoader() {
		if (!isset($this->loader)) {
			$this->loader = new Mustache_Loader_StringLoader;
		}

		return $this->loader;
	}

	/**
	 * Set the Mustache partials Loader instance.
	 *
	 * @param Mustache_Loader $partialsLoader
	 */
	public function setPartialsLoader(Mustache_Loader $partialsLoader) {
		$this->partialsLoader = $partialsLoader;
	}

	/**
	 * Get the current Mustache partials Loader instance.
	 *
	 * If no Loader instance has been explicitly specified, this method will instantiate and return
	 * an ArrayLoader instance.
	 *
	 * @return Mustache_Loader
	 */
	public function getPartialsLoader() {
		if (!isset($this->partialsLoader)) {
			$this->partialsLoader = new Mustache_Loader_ArrayLoader;
		}

		return $this->partialsLoader;
	}

	/**
	 * Set partials for the current partials Loader instance.
	 *
	 * @throws RuntimeException If the current Loader instance is immutable
	 *
	 * @param array $partials (default: array())
	 */
	public function setPartials(array $partials = array()) {
		$loader = $this->getPartialsLoader();
		if (!$loader instanceof Mustache_Loader_MutableLoader) {
			throw new RuntimeException('Unable to set partials on an immutable Mustache Loader instance');
		}

		$loader->setTemplates($partials);
	}

	/**
	 * Set the Mustache Tokenizer instance.
	 *
	 * @param Mustache_Tokenizer $tokenizer
	 */
	public function setTokenizer(Mustache_Tokenizer $tokenizer) {
		$this->tokenizer = $tokenizer;
	}

	/**
	 * Get the current Mustache Tokenizer instance.
	 *
	 * If no Tokenizer instance has been explicitly specified, this method will instantiate and return a new one.
	 *
	 * @return Mustache_Tokenizer
	 */
	public function getTokenizer() {
		if (!isset($this->tokenizer)) {
			$this->tokenizer = new Mustache_Tokenizer;
		}

		return $this->tokenizer;
	}

	/**
	 * Set the Mustache Parser instance.
	 *
	 * @param Mustache_Parser $parser
	 */
	public function setParser(Mustache_Parser $parser) {
		$this->parser = $parser;
	}

	/**
	 * Get the current Mustache Parser instance.
	 *
	 * If no Parser instance has been explicitly specified, this method will instantiate and return a new one.
	 *
	 * @return Mustache_Parser
	 */
	public function getParser() {
		if (!isset($this->parser)) {
			$this->parser = new Mustache_Parser;
		}

		return $this->parser;
	}

	/**
	 * Set the Mustache Compiler instance.
	 *
	 * @param Mustache_Compiler $compiler
	 */
	public function setCompiler(Mustache_Compiler $compiler) {
		$this->compiler = $compiler;
	}

	/**
	 * Get the current Mustache Compiler instance.
	 *
	 * If no Compiler instance has been explicitly specified, this method will instantiate and return a new one.
	 *
	 * @return Mustache_Compiler
	 */
	public function getCompiler() {
		if (!isset($this->compiler)) {
			$this->compiler = new Mustache_Compiler;
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
	 * @return Mustache_Template
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
	 * @return Mustache_Template
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
	 * @return Mustache_Template
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
	 * @see Mustache_Mustache::loadTemplate
	 * @see Mustache_Mustache::loadPartial
	 * @see Mustache_Mustache::loadLambda
	 *
	 * @param string $source
	 *
	 * @return Mustache_Template
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
	 * @see Mustache_Tokenizer::scan
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
	 * @see Mustache_Parser::parse
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
	 * @see Mustache_Compiler::compile
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
	 * @throws RuntimeException if unable to write to $fileName.
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

		throw new RuntimeException(sprintf('Failed to write cache file "%s".', $fileName));
	}
}
