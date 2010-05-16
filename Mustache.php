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

	public $_otag = '{{';
	public $_ctag = '}}';

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

	const PRAGMA_DOT_NOTATION = 'DOT-NOTATION';

	/**
	 * The {{%UNESCAPED}} pragma swaps the meaning of the {{normal}} and {{{unescaped}}}
	 * Mustache tags. That is, once this pragma is activated the {{normal}} tag will not be
	 * escaped while the {{{unescaped}}} tag will be escaped.
	 *
	 * Pragmas apply only to the current template. Partials, even those included after the
	 * {{%UNESCAPED}} call, will need their own pragma declaration.
	 *
	 * his may be useful in non-HTML Mustache situations.
	 */
	const PRAGMA_UNESCAPED    = 'UNESCAPED';

	protected $_tagRegEx;

	protected $_template = '';
	protected $_context  = array();
	protected $_partials = array();
	protected $_pragmas  = array();

	protected $_pragmasImplemented = array(
		self::PRAGMA_DOT_NOTATION,
		self::PRAGMA_UNESCAPED
	);

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
	 * @param array &$context
	 * @return string Rendered Mustache template.
	 */
	protected function _renderTemplate($template, &$context) {
		$template = $this->_renderPragmas($template, $context);
		$template = $this->_renderSection($template, $context);
		return $this->_renderTags($template, $context);
	}

	/**
	 * Render boolean, enumerable and inverted sections.
	 *
	 * @access protected
	 * @param string $template
	 * @param array $context
	 * @return string
	 */
	protected function _renderSection($template, &$context) {
		$otag  = $this->_prepareRegEx($this->_otag);
		$ctag  = $this->_prepareRegEx($this->_ctag);
		$regex = '/' . $otag . '(\\^|\\#)(.+?)' . $ctag . '\\s*([\\s\\S]+?)' . $otag . '\\/\\2' . $ctag . '\\s*/m';

		$matches = array();
		while (preg_match($regex, $template, $matches, PREG_OFFSET_CAPTURE)) {
			$section  = $matches[0][0];
			$offset   = $matches[0][1];
			$type     = $matches[1][0];
			$tag_name = trim($matches[2][0]);
			$content  = $matches[3][0];

			$replace = '';
			$val = $this->_getVariable($tag_name, $context);
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
						foreach ($val as $local_context) {
							$c = $this->_getContext($context, $local_context);
							$replace .= $this->_renderTemplate($content, $c);
						}
					} else if ($val) {
						if (is_array($val) || is_object($val)) {
							$c = $this->_getContext($context, $val);
							$replace .= $this->_renderTemplate($content, $c);
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
	 * Initialize pragmas and remove all pragma tags.
	 *
	 * @access protected
	 * @param string $template
	 * @param array &$context
	 * @return string
	 */
	protected function _renderPragmas($template, &$context) {
		// no pragmas
		if (strpos($template, $this->_otag . '%') === false) {
			return $template;
		}

		$otag = $this->_prepareRegEx($this->_otag);
		$ctag = $this->_prepareRegEx($this->_ctag);
		$regex = '/' . $otag . '%\\s*([\\w_-]+)((?: [\\w]+=[\\w]+)*)\\s*' . $ctag . '\\n?/';
		return preg_replace_callback($regex, array($this, '_renderPragma'), $template);
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
		$pragma_name    = $matches[1];
		$options_string = $matches[2];

		if (!in_array($pragma_name, $this->_pragmasImplemented)) {
			throw new MustacheException('Unknown pragma: ' . $pragma_name, MustacheException::UNKNOWN_PRAGMA);
		}

		$options = array();
		foreach (explode(' ', trim($options_string)) as $o) {
			if ($p = trim($o)) {
				$p = explode('=', trim($p));
				$options[$p[0]] = $p[1];
			}
		}

		if (empty($options)) {
			$this->_pragmas[$pragma_name] = true;
		} else {
			$this->_pragmas[$pragma_name] = $options;
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
		if (array_key_exists($pragma_name, $this->_pragmas) && $this->_pragmas[$pragma_name]) {
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
		if (!$this->_hasPragma()) {
			throw new MustacheException('Unknown pragma: ' . $pragma_name, MustacheException::UNKNOWN_PRAGMA);
		}

		return $this->_pragmas[$pragma_name];
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
	 * Loop through and render individual Mustache tags.
	 *
	 * @access protected
	 * @param string $template
	 * @param array $context
	 * @return void
	 */
	protected function _renderTags($template, &$context) {
		if (strpos($template, $this->_otag) === false) {
			return $template;
		}

		$otag = $this->_prepareRegEx($this->_otag);
		$ctag = $this->_prepareRegEx($this->_ctag);

		$this->_tagRegEx = '/' . $otag . "([#\^\/=!>\\{&])?(.+?)\\1?" . $ctag . "+/";

		$html = '';
		$matches = array();
		while (preg_match($this->_tagRegEx, $template, $matches, PREG_OFFSET_CAPTURE)) {
			$tag      = $matches[0][0];
			$offset   = $matches[0][1];
			$modifier = $matches[1][0];
			$tag_name = trim($matches[2][0]);

			$html .= substr($template, 0, $offset);
			$html .= $this->_renderTag($modifier, $tag_name, $context);
			$template = substr($template, $offset + strlen($tag));
		}

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
	 * @param array $context
	 * @throws MustacheException Unmatched section tag encountered.
	 * @return string
	 */
	protected function _renderTag($modifier, $tag_name, &$context) {
		switch ($modifier) {
			case '#':
			case '^':
				if ($this->_throwsException(MustacheException::UNCLOSED_SECTION)) {
					throw new MustacheException('Unclosed section: ' . $tag_name, MustacheException::UNCLOSED_SECTION);
				} else {
					return '';
				}
				break;
			case '/':
				if ($this->_throwsException(MustacheException::UNEXPECTED_CLOSE_SECTION)) {
					throw new MustacheException('Unexpected close section: ' . $tag_name, MustacheException::UNEXPECTED_CLOSE_SECTION);
				} else {
					return '';
				}
				break;
			case '=':
				return $this->_changeDelimiter($tag_name, $context);
				break;
			case '!':
				return $this->_renderComment($tag_name, $context);
				break;
			case '>':
				return $this->_renderPartial($tag_name, $context);
				break;
			case '{':
			case '&':
				if ($this->_hasPragma(self::PRAGMA_UNESCAPED)) {
					return $this->_renderEscaped($tag_name, $context);
				} else {
					return $this->_renderUnescaped($tag_name, $context);
				}
				break;
			case '':
			default:
				if ($this->_hasPragma(self::PRAGMA_UNESCAPED)) {
					return $this->_renderUnescaped($tag_name, $context);
				} else {
					return $this->_renderEscaped($tag_name, $context);
				}
				break;
		}
	}

	/**
	 * Escape and return the requested tag.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param array $context
	 * @return string
	 */
	protected function _renderEscaped($tag_name, &$context) {
		return htmlentities($this->_getVariable($tag_name, $context), null, $this->_charset);
	}

	/**
	 * Render a comment (i.e. return an empty string).
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param array $context
	 * @return string
	 */
	protected function _renderComment($tag_name, &$context) {
		return '';
	}

	/**
	 * Return the requested tag unescaped.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param array $context
	 * @return string
	 */
	protected function _renderUnescaped($tag_name, &$context) {
		return $this->_getVariable($tag_name, $context);
	}

	/**
	 * Render the requested partial.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param array $context
	 * @return string
	 */
	protected function _renderPartial($tag_name, &$context) {
		$view = new self($this->_getPartial($tag_name), $this->_flattenContext($context), $this->_partials);
		$view->_otag = $this->_otag;
		$view->_ctag = $this->_ctag;
		return $view->render();
	}

	/**
	 * Change the Mustache tag delimiter. This method also replaces this object's current
	 * tag RegEx with one using the new delimiters.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param array $context
	 * @return string
	 */
	protected function _changeDelimiter($tag_name, &$context) {
		$tags = explode(' ', $tag_name);
		$this->_otag = $tags[0];
		$this->_ctag = $tags[1];

		$otag  = $this->_prepareRegEx($this->_otag);
		$ctag  = $this->_prepareRegEx($this->_ctag);
		$this->_tagRegEx = '/' . $otag . "([#\^\/=!>\\{&])?(.+?)\\1?" . $ctag . "+/";
		return '';
	}


	/**
	 * Prepare a new context reference array.
	 *
	 * This is used to create context arrays for iterable blocks.
	 *
	 * @access protected
	 * @param array $context
	 * @param array $local_context
	 * @return array
	 */
	protected function _getContext(&$context, &$local_context) {
		$ret = array();
		$ret[] =& $local_context;
		foreach ($context as $view) {
			$ret[] =& $view;
		}
		return $ret;
	}


	/**
	 * Prepare a new (flattened) context.
	 *
	 * This is used to create a view object or array for rendering partials.
	 *
	 * @access protected
	 * @param array &$context
	 * @return array
	 * @throws MustacheException
	 */
	protected function _flattenContext(&$context) {
		$keys = array_keys($context);
		$first = $context[$keys[0]];

		if ($first instanceof Mustache) {
			$ret = clone $first;
			unset($keys[0]);

			foreach ($keys as $name) {
				foreach ($context[$name] as $key => $val) {
					$ret->$key =& $val;
				}
			}
		} else if (is_array($first)) {
			$ret = array();

			foreach ($keys as $name) {
				foreach ($context[$name] as $key => $val) {
					$ret[$key] =& $val;
				}
			}
		} else {
			throw new MustacheException('Unknown root context type.');
		}

		return $ret;
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
	 * @param array $context
	 * @throws MustacheException Unknown variable name.
	 * @return string
	 */
	protected function _getVariable($tag_name, &$context) {
		if ($this->_hasPragma(self::PRAGMA_DOT_NOTATION)) {
			$chunks = explode('.', $tag_name);
			$first = array_shift($chunks);

			$ret = $this->_findVariableInContext($first, $context);
			while ($next = array_shift($chunks)) {
				// Slice off a chunk of context for dot notation traversal.
				$c = array($ret);
				$ret = $this->_findVariableInContext($next, $c);
			}
			return $ret;
		} else {
			return $this->_findVariableInContext($tag_name, $context);
		}
	}

	/**
	 * Get a variable from the context array. Internal helper used by getVariable() to abstract
	 * variable traversal for dot notation.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param array &$context
	 * @throws MustacheException Unknown variable name.
	 * @return string
	 */
	protected function _findVariableInContext($tag_name, &$context) {
		foreach ($context as $view) {
			if (is_object($view)) {
				if (isset($view->$tag_name)) {
					return $view->$tag_name;
				} else if (method_exists($view, $tag_name)) {
					return $view->$tag_name();
				}
			} else if (isset($view[$tag_name])) {
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
		return is_object($var) || (is_array($var) && !array_diff_key($var, array_keys(array_keys($var))));
	}

	/**
	 * Prepare a string to be used in a regular expression.
	 *
	 * @access protected
	 * @param string $str
	 * @return string
	 */
	protected function _prepareRegEx($str) {
		$replace = array(
			'\\' => '\\\\', '^' => '\^', '.' => '\.', '$' => '\$', '|' => '\|', '(' => '\(',
			')' => '\)', '[' => '\[', ']' => '\]', '*' => '\*', '+' => '\+', '?' => '\?',
			'{' => '\{', '}' => '\}', ',' => '\,'
		);
		return strtr($str, $replace);
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