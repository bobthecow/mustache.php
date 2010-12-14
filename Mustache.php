<?php

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

	/**
	 * Should this Mustache throw exceptions when it finds unexpected tags?
	 *
	 * @see self::_throwsException()
	 */
	protected $_throwsExceptions = array(
		MustacheException::UNKNOWN_VARIABLE         => false,
		MustacheException::UNCLOSED_SECTION         => true,
		MustacheException::UNEXPECTED_CLOSE_SECTION => true,
		MustacheException::UNKNOWN_PARTIAL          => false,
		MustacheException::UNKNOWN_PRAGMA           => true,
	);

	// Override charset passed to htmlentities() and htmlspecialchars(). Defaults to UTF-8.
	protected $_charset = 'UTF-8';

	/**
	 * Pragmas are macro-like directives that, when invoked, change the behavior or
	 * syntax of Mustache.
	 *
	 * They should be considered extremely experimental. Most likely their implementation
	 * will change in the future.
	 */

	/**
	 * The {{%DOT-NOTATION}} pragma allows context traversal via dots. Given the following context:
	 *
	 *     $context = array('foo' => array('bar' => array('baz' => 'qux')));
	 *
	 * One could access nested properties using dot notation:
	 *
	 *      {{%DOT-NOTATION}}{{foo.bar.baz}}
	 *
	 * Which would render as `qux`.
	 */
	const PRAGMA_DOT_NOTATION      = 'DOT-NOTATION';

	/**
	 * The {{%IMPLICIT-ITERATOR}} pragma allows access to non-associative array data in an
	 * iterable section:
	 *
	 *     $context = array('items' => array('foo', 'bar', 'baz'));
	 *
	 * With this template:
	 *
	 *     {{%IMPLICIT-ITERATOR}}{{#items}}{{.}}{{/items}}
	 *
	 * Would render as `foobarbaz`.
	 *
	 * {{%IMPLICIT-ITERATOR}} accepts an optional 'iterator' argument which allows implicit
	 * iterator tags other than {{.}} ...
	 *
	 *     {{%IMPLICIT-ITERATOR iterator=i}}{{#items}}{{i}}{{/items}}
	 */
	const PRAGMA_IMPLICIT_ITERATOR = 'IMPLICIT-ITERATOR';

	/**
	 * The {{%UNESCAPED}} pragma swaps the meaning of the {{normal}} and {{{unescaped}}}
	 * Mustache tags. That is, once this pragma is activated the {{normal}} tag will not be
	 * escaped while the {{{unescaped}}} tag will be escaped.
	 *
	 * Pragmas apply only to the current template. Partials, even those included after the
	 * {{%UNESCAPED}} call, will need their own pragma declaration.
	 *
	 * This may be useful in non-HTML Mustache situations.
	 */
	const PRAGMA_UNESCAPED    = 'UNESCAPED';

	/**
	 * Constants used for section and tag RegEx
	 */
	const SECTION_TYPES = '\^#\/';
	const TAG_TYPES = '#\^\/=!<>\\{&';

	public $_otag = '{{';
	public $_ctag = '}}';

	protected $_tagRegEx;

	protected $_template = '';
	protected $_context  = array();
	protected $_partials = array();
	protected $_pragmas  = array();

	protected $_pragmasImplemented = array(
		self::PRAGMA_DOT_NOTATION,
		self::PRAGMA_IMPLICIT_ITERATOR,
		self::PRAGMA_UNESCAPED
	);

	protected $_localPragmas = array();

	/**
	 * Mustache class constructor.
	 *
	 * This method accepts a $template string and a $view object. Optionally, pass an associative
	 * array of partials as well.
	 *
	 * @access public
	 * @param string $template (default: null)
	 * @param mixed $view (default: null)
	 * @param array $partials (default: null)
	 * @return void
	 */
	public function __construct($template = null, $view = null, $partials = null) {
		if ($template !== null) $this->_template = $template;
		if ($partials !== null) $this->_partials = $partials;
		if ($view !== null)     $this->_context = array($view);
	}

	/**
	 * Mustache class clone method.
	 *
	 * A cloned Mustache instance should have pragmas, delimeters and root context
	 * reset to default values.
	 *
	 * @access public
	 * @return void
	 */
	public function __clone() {
		$this->_otag = '{{';
		$this->_ctag = '}}';
		$this->_localPragmas = array();

		if ($keys = array_keys($this->_context)) {
			$last = array_pop($keys);
			if ($this->_context[$last] instanceof Mustache) {
				$this->_context[$last] =& $this;
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
		if ($template === null) $template = $this->_template;
		if ($partials !== null) $this->_partials = $partials;

		if ($view) {
			$this->_context = array($view);
		} else if (empty($this->_context)) {
			$this->_context = array($this);
		}

		$template = $this->_renderPragmas($template);
		return $this->_renderTemplate($template, $this->_context);
	}

	/**
	 * Wrap the render() function for string conversion.
	 *
	 * @access public
	 * @return string
	 */
	public function __toString() {
		// PHP doesn't like exceptions in __toString.
		// catch any exceptions and convert them to strings.
		try {
			$result = $this->render();
			return $result;
		} catch (Exception $e) {
			return "Error rendering mustache: " . $e->getMessage();
		}
	}

	/**
	 * Internal render function, used for recursive calls.
	 *
	 * @access protected
	 * @param string $template
	 * @return string Rendered Mustache template.
	 */
	protected function _renderTemplate($template) {
		$template = $this->_renderSections($template);
		return $this->_renderTags($template);
	}

	/**
	 * Render boolean, enumerable and inverted sections.
	 *
	 * @access protected
	 * @param string $template
	 * @return string
	 */
	protected function _renderSections($template) {
		while ($section_data = $this->_findSection($template)) {
			list($section, $offset, $type, $tag_name, $content) = $section_data;

			$replace = '';
			$val = $this->_getVariable($tag_name);
			switch($type) {
				// inverted section
				case '^':
					if (empty($val)) {
						$replace .= $content;
					}
					break;

				// regular section
				case '#':
					if ($this->_varIsIterable($val)) {
						if ($this->_hasPragma(self::PRAGMA_IMPLICIT_ITERATOR)) {
							if ($opt = $this->_getPragmaOptions(self::PRAGMA_IMPLICIT_ITERATOR)) {
								$iterator = $opt['iterator'];
							} else {
								$iterator = '.';
							}
						} else {
							$iterator = false;
						}

						foreach ($val as $local_context) {

							if ($iterator) {
								$iterator_context = array($iterator => $local_context);
								$this->_pushContext($iterator_context);
							} else {
								$this->_pushContext($local_context);
							}
							$replace .= $this->_renderTemplate($content);
							$this->_popContext();
						}
					} else if ($val) {
						if (is_array($val) || is_object($val)) {
							$this->_pushContext($val);
							$replace .= $this->_renderTemplate($content);
							$this->_popContext();
						} else {
							$replace .= $content;
						}
					}
					break;
			}

			$template = substr_replace($template, $replace, $offset, strlen($section));
		}

		return $template;
	}

	/**
	 * Prepare a section RegEx string for the given opening/closing tags.
	 *
	 * @access protected
	 * @param string $otag
	 * @param string $ctag
	 * @return string
	 */
	protected function _prepareSectionRegEx($otag, $ctag) {
		return sprintf(
			'/(?:(?<=\\n)[ \\t]*)?%s(?P<type>[%s])(?P<tag_name>.+?)%s\\n?/s',
			preg_quote($otag, '/'),
			self::SECTION_TYPES,
			preg_quote($ctag, '/')
		);
	}

	/**
	 * Extract a section from $template.
	 *
	 * This is a helper function to find sections needed by _renderSections.
	 *
	 * @access protected
	 * @param string $template
	 * @return array $section, $offset, $type, $tag_name and $content
	 */
	protected function _findSection($template) {
		$regEx = $this->_prepareSectionRegEx($this->_otag, $this->_ctag);

		$section_start = null;
		$section_type  = null;
		$content_start = null;

		$search_offset = 0;

		$section_stack = array();
		$matches = array();
		while (preg_match($regEx, $template, $matches, PREG_OFFSET_CAPTURE, $search_offset)) {

			$match    = $matches[0][0];
			$offset   = $matches[0][1];
			$type     = $matches['type'][0];
			$tag_name = trim($matches['tag_name'][0]);

			$search_offset = $offset + strlen($match);

			switch ($type) {
				case '^':
				case '#':
					if (empty($section_stack)) {
						$section_start = $offset;
						$section_type  = $type;
						$content_start = $search_offset;
					}
					array_push($section_stack, $tag_name);
					break;
				case '/':
					if (empty($section_stack) || ($tag_name !== array_pop($section_stack))) {
						if ($this->_throwsException(MustacheException::UNEXPECTED_CLOSE_SECTION)) {
							throw new MustacheException('Unexpected close section: ' . $tag_name, MustacheException::UNEXPECTED_CLOSE_SECTION);
						}
					}

					if (empty($section_stack)) {
						$section = substr($template, $section_start, $search_offset - $section_start);
						$content = substr($template, $content_start, $offset - $content_start);

						return array($section, $section_start, $section_type, $tag_name, $content);
					}
					break;
			}
		}

		if (!empty($section_stack)) {
			if ($this->_throwsException(MustacheException::UNCLOSED_SECTION)) {
				throw new MustacheException('Unclosed section: ' . $section_stack[0], MustacheException::UNCLOSED_SECTION);
			}
		}
	}

	/**
	 * Prepare a pragma RegEx for the given opening/closing tags.
	 *
	 * @access protected
	 * @param string $otag
	 * @param string $ctag
	 * @return string
	 */
	protected function _preparePragmaRegEx($otag, $ctag) {
		return sprintf(
			'/%s%%\\s*(?P<pragma_name>[\\w_-]+)(?P<options_string>(?: [\\w]+=[\\w]+)*)\\s*%s\\n?/s',
			preg_quote($otag, '/'),
			preg_quote($ctag, '/')
		);
	}

	/**
	 * Initialize pragmas and remove all pragma tags.
	 *
	 * @access protected
	 * @param string $template
	 * @return string
	 */
	protected function _renderPragmas($template) {
		$this->_localPragmas = $this->_pragmas;

		// no pragmas
		if (strpos($template, $this->_otag . '%') === false) {
			return $template;
		}

		$regEx = $this->_preparePragmaRegEx($this->_otag, $this->_ctag);
		return preg_replace_callback($regEx, array($this, '_renderPragma'), $template);
	}

	/**
	 * A preg_replace helper to remove {{%PRAGMA}} tags and enable requested pragma.
	 *
	 * @access protected
	 * @param mixed $matches
	 * @return void
	 * @throws MustacheException unknown pragma
	 */
	protected function _renderPragma($matches) {
		$pragma         = $matches[0];
		$pragma_name    = $matches['pragma_name'];
		$options_string = $matches['options_string'];

		if (!in_array($pragma_name, $this->_pragmasImplemented)) {
			throw new MustacheException('Unknown pragma: ' . $pragma_name, MustacheException::UNKNOWN_PRAGMA);
		}

		$options = array();
		foreach (explode(' ', trim($options_string)) as $o) {
			if ($p = trim($o)) {
				$p = explode('=', $p);
				$options[$p[0]] = $p[1];
			}
		}

		if (empty($options)) {
			$this->_localPragmas[$pragma_name] = true;
		} else {
			$this->_localPragmas[$pragma_name] = $options;
		}

		return '';
	}

	/**
	 * Check whether this Mustache has a specific pragma.
	 *
	 * @access protected
	 * @param string $pragma_name
	 * @return bool
	 */
	protected function _hasPragma($pragma_name) {
		if (array_key_exists($pragma_name, $this->_localPragmas) && $this->_localPragmas[$pragma_name]) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Return pragma options, if any.
	 *
	 * @access protected
	 * @param string $pragma_name
	 * @return mixed
	 * @throws MustacheException Unknown pragma
	 */
	protected function _getPragmaOptions($pragma_name) {
		if (!$this->_hasPragma($pragma_name)) {
			throw new MustacheException('Unknown pragma: ' . $pragma_name, MustacheException::UNKNOWN_PRAGMA);
		}

		return (is_array($this->_localPragmas[$pragma_name])) ? $this->_localPragmas[$pragma_name] : array();
	}

	/**
	 * Check whether this Mustache instance throws a given exception.
	 *
	 * Expects exceptions to be MustacheException error codes (i.e. class constants).
	 *
	 * @access protected
	 * @param mixed $exception
	 * @return void
	 */
	protected function _throwsException($exception) {
		return (isset($this->_throwsExceptions[$exception]) && $this->_throwsExceptions[$exception]);
	}

	/**
	 * Prepare a tag RegEx for the given opening/closing tags.
	 *
	 * @access protected
	 * @param string $otag
	 * @param string $ctag
	 * @return string
	 */
	protected function _prepareTagRegEx($otag, $ctag) {
		return sprintf(
			'/(?P<whitespace>(?<=\\n)[ \\t]*)?%s(?P<type>[%s]?)(?P<tag_name>.+?)(?:\\2|})?%s(?:\\s*(?=\\n))?/s',
			preg_quote($otag, '/'),
			self::TAG_TYPES,
			preg_quote($ctag, '/')
		);
	}

	/**
	 * Loop through and render individual Mustache tags.
	 *
	 * @access protected
	 * @param string $template
	 * @return void
	 */
	protected function _renderTags($template) {
		if (strpos($template, $this->_otag) === false) {
			return $template;
		}

		$otag_orig = $this->_otag;
		$ctag_orig = $this->_ctag;

		$this->_tagRegEx = $this->_prepareTagRegEx($this->_otag, $this->_ctag);

		$html = '';
		$matches = array();
		while (preg_match($this->_tagRegEx, $template, $matches, PREG_OFFSET_CAPTURE)) {
			$tag      = $matches[0][0];
			$offset   = $matches[0][1];
			$modifier = $matches['type'][0];
			$tag_name = trim($matches['tag_name'][0]);

			if (isset($matches['whitespace']) && $matches['whitespace'][1] > -1) {
				$whitespace = $matches['whitespace'][0];
			} else {
				$whitespace = null;
			}

			$html .= substr($template, 0, $offset);

			$next_offset = $offset + strlen($tag);
			if ((substr($html, -1) == "\n") && (substr($template, $next_offset, 1) == "\n")) {
				$next_offset++;
			}
			$template = substr($template, $next_offset);

			$html .= $this->_renderTag($modifier, $tag_name, $whitespace);
		}

		$this->_otag = $otag_orig;
		$this->_ctag = $ctag_orig;

		return $html . $template;
	}

	/**
	 * Render the named tag, given the specified modifier.
	 *
	 * Accepted modifiers are `=` (change delimiter), `!` (comment), `>` (partial)
	 * `{` or `&` (don't escape output), or none (render escaped output).
	 *
	 * @access protected
	 * @param string $modifier
	 * @param string $tag_name
	 * @throws MustacheException Unmatched section tag encountered.
	 * @return string
	 */
	protected function _renderTag($modifier, $tag_name, $whitespace) {
		switch ($modifier) {
			case '=':
				return $this->_changeDelimiter($tag_name);
				break;
			case '!':
				return $this->_renderComment($tag_name);
				break;
			case '>':
			case '<':
				return $this->_renderPartial($tag_name, $whitespace);
				break;
			case '{':
				// strip the trailing } ...
				if ($tag_name[(strlen($tag_name) - 1)] == '}') {
					$tag_name = substr($tag_name, 0, -1);
				}
			case '&':
				if ($this->_hasPragma(self::PRAGMA_UNESCAPED)) {
					return $this->_renderEscaped($tag_name);
				} else {
					return $this->_renderUnescaped($tag_name);
				}
				break;
			case '#':
			case '^':
			case '/':
				// remove any leftovers from _renderSections
				return '';
				break;
		}

		if ($this->_hasPragma(self::PRAGMA_UNESCAPED)) {
			return $this->_renderUnescaped($modifier . $tag_name);
		} else {
			return $this->_renderEscaped($modifier . $tag_name);
		}
	}

	/**
	 * Escape and return the requested tag.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @return string
	 */
	protected function _renderEscaped($tag_name) {
		return htmlentities($this->_getVariable($tag_name), ENT_COMPAT, $this->_charset);
	}

	/**
	 * Render a comment (i.e. return an empty string).
	 *
	 * @access protected
	 * @param string $tag_name
	 * @return string
	 */
	protected function _renderComment($tag_name) {
		return '';
	}

	/**
	 * Return the requested tag unescaped.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @return string
	 */
	protected function _renderUnescaped($tag_name) {
		return $this->_getVariable($tag_name);
	}

	/**
	 * Render the requested partial.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @return string
	 */
	protected function _renderPartial($tag_name, $whitespace = '') {
		$view = clone($this);

		return $whitespace . preg_replace('/\n(?!$)/s', "\n" . $whitespace, $view->render($this->_getPartial($tag_name)));
	}

	/**
	 * Change the Mustache tag delimiter. This method also replaces this object's current
	 * tag RegEx with one using the new delimiters.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @return string
	 */
	protected function _changeDelimiter($tag_name) {
		list($otag, $ctag) = explode(' ', $tag_name);
		$this->_otag = $otag;
		$this->_ctag = $ctag;

		$this->_tagRegEx = $this->_prepareTagRegEx($this->_otag, $this->_ctag);

		return '';
	}

	/**
	 * Push a local context onto the stack.
	 *
	 * @access protected
	 * @param array &$local_context
	 * @return void
	 */
	protected function _pushContext(&$local_context) {
		$new = array();
		$new[] =& $local_context;
		foreach (array_keys($this->_context) as $key) {
			$new[] =& $this->_context[$key];
		}
		$this->_context = $new;
	}

	/**
	 * Remove the latest context from the stack.
	 *
	 * @access protected
	 * @return void
	 */
	protected function _popContext() {
		$new = array();

		$keys = array_keys($this->_context);
		array_shift($keys);
		foreach ($keys as $key) {
			$new[] =& $this->_context[$key];
		}
		$this->_context = $new;
	}

	/**
	 * Get a variable from the context array.
	 *
	 * If the view is an array, returns the value with array key $tag_name.
	 * If the view is an object, this will check for a public member variable
	 * named $tag_name. If none is available, this method will execute and return
	 * any class method named $tag_name. Failing all of the above, this method will
	 * return an empty string.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @throws MustacheException Unknown variable name.
	 * @return string
	 */
	protected function _getVariable($tag_name) {
		if ($tag_name != '.' && strpos($tag_name, '.') !== false && $this->_hasPragma(self::PRAGMA_DOT_NOTATION)) {
			$chunks = explode('.', $tag_name);
			$first = array_shift($chunks);

			$ret = $this->_findVariableInContext($first, $this->_context);
			while ($next = array_shift($chunks)) {
				// Slice off a chunk of context for dot notation traversal.
				$c = array($ret);
				$ret = $this->_findVariableInContext($next, $c);
			}
			return $ret;
		} else {
			return $this->_findVariableInContext($tag_name, $this->_context);
		}
	}

	/**
	 * Get a variable from the context array. Internal helper used by getVariable() to abstract
	 * variable traversal for dot notation.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param array $context
	 * @throws MustacheException Unknown variable name.
	 * @return string
	 */
	protected function _findVariableInContext($tag_name, $context) {
		foreach ($context as $view) {
			if (is_object($view)) {
				if (method_exists($view, $tag_name)) {
					return $view->$tag_name();
				} else if (isset($view->$tag_name)) {
					return $view->$tag_name;
				}
			} else if (array_key_exists($tag_name, $view)) {
				return $view[$tag_name];
			}
		}

		if ($this->_throwsException(MustacheException::UNKNOWN_VARIABLE)) {
			throw new MustacheException("Unknown variable: " . $tag_name, MustacheException::UNKNOWN_VARIABLE);
		} else {
			return '';
		}
	}

	/**
	 * Retrieve the partial corresponding to the requested tag name.
	 *
	 * Silently fails (i.e. returns '') when the requested partial is not found.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @throws MustacheException Unknown partial name.
	 * @return string
	 */
	protected function _getPartial($tag_name) {
		if (is_array($this->_partials) && isset($this->_partials[$tag_name])) {
			return $this->_partials[$tag_name];
		}

		if ($this->_throwsException(MustacheException::UNKNOWN_PARTIAL)) {
			throw new MustacheException('Unknown partial: ' . $tag_name, MustacheException::UNKNOWN_PARTIAL);
		} else {
			return '';
		}
	}

	/**
	 * Check whether the given $var should be iterated (i.e. in a section context).
	 *
	 * @access protected
	 * @param mixed $var
	 * @return bool
	 */
	protected function _varIsIterable($var) {
		return $var instanceof Traversable || (is_array($var) && !array_diff_key($var, array_keys(array_keys($var))));
	}
}


/**
 * MustacheException class.
 *
 * @extends Exception
 */
class MustacheException extends Exception {

	// An UNKNOWN_VARIABLE exception is thrown when a {{variable}} is not found
	// in the current context.
	const UNKNOWN_VARIABLE         = 0;

	// An UNCLOSED_SECTION exception is thrown when a {{#section}} is not closed.
	const UNCLOSED_SECTION         = 1;

	// An UNEXPECTED_CLOSE_SECTION exception is thrown when {{/section}} appears
	// without a corresponding {{#section}} or {{^section}}.
	const UNEXPECTED_CLOSE_SECTION = 2;

	// An UNKNOWN_PARTIAL exception is thrown whenever a {{>partial}} tag appears
	// with no associated partial.
	const UNKNOWN_PARTIAL          = 3;

	// An UNKNOWN_PRAGMA exception is thrown whenever a {{%PRAGMA}} tag appears
	// which can't be handled by this Mustache instance.
	const UNKNOWN_PRAGMA           = 4;

}