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

	public $otag = '{{';
	public $ctag = '}}';

	// Should this Mustache throw exceptions when it finds unexpected tags?
	protected $throwSectionExceptions  = true;
	protected $throwPartialExceptions  = false;
	protected $throwVariableExceptions = false;

	// Override charset passed to htmlentities() and htmlspecialchars(). Defaults to UTF-8.
	protected $charset = 'UTF-8';

	protected $tagRegEx;

	protected $template = '';
	protected $context  = array();
	protected $partials = array();

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
		if ($template !== null) $this->template = $template;
		if ($partials !== null) $this->partials = $partials;
		if ($view !== null)     $this->context = array($view);
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
		if ($template === null) $template = $this->template;
		if ($partials !== null) $this->partials = $partials;

		if ($view) {
			$this->context = array($view);
		} else if (empty($this->context)) {
			$this->context = array($this);
		}

		return $this->_render($template, $this->context);
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
	protected function _render($template, &$context) {
		$template = $this->renderSection($template, $context);
		return $this->renderTags($template, $context);
	}

	/**
	 * Render boolean, enumerable and inverted sections.
	 *
	 * @access protected
	 * @param string $template
	 * @param array $context
	 * @return string
	 */
	protected function renderSection($template, &$context) {
		if (strpos($template, $this->otag . '#') === false) {
			return $template;
		}

		$otag  = $this->prepareRegEx($this->otag);
		$ctag  = $this->prepareRegEx($this->ctag);
		$regex = '/' . $otag . '(\\^|\\#)(.+?)' . $ctag . '\\s*([\\s\\S]+?)' . $otag . '\\/\\2' . $ctag . '\\s*/m';

		$matches = array();
		while (preg_match($regex, $template, $matches, PREG_OFFSET_CAPTURE)) {
			$section  = $matches[0][0];
			$offset   = $matches[0][1];
			$type     = $matches[1][0];
			$tag_name = trim($matches[2][0]);
			$content  = $matches[3][0];

			$replace = '';
			$val = $this->getVariable($tag_name, $context);
			switch($type) {
				// inverted section
				case '^':
					if (empty($val)) {
						$replace .= $content;
					}
					break;

				// regular section
				case '#':
					if ($this->varIsIterable($val)) {
						foreach ($val as $local_context) {
							$c = $this->getContext($context, $local_context);
							$replace .= $this->_render($content, $c);
						}
					} else if ($val) {
						if (is_array($val) || is_object($val)) {
							$replace .= $this->_render($content, $this->getContext($context, $val));
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
	 * Loop through and render individual Mustache tags.
	 *
	 * @access protected
	 * @param string $template
	 * @param array $context
	 * @return void
	 */
	protected function renderTags($template, &$context) {
		if (strpos($template, $this->otag) === false) {
			return $template;
		}

		$otag  = $this->prepareRegEx($this->otag);
		$ctag  = $this->prepareRegEx($this->ctag);
		$this->tagRegEx = '/' . $otag . "(#|\/|=|!|>|\\{|&)?([^\/#]+?)\\1?" . $ctag . "+/";
		$html = '';
		$matches = array();
		while (preg_match($this->tagRegEx, $template, $matches, PREG_OFFSET_CAPTURE)) {
			$tag      = $matches[0][0];
			$offset   = $matches[0][1];
			$modifier = $matches[1][0];
			$tag_name = trim($matches[2][0]);

			$html .= substr($template, 0, $offset);
			$html .= $this->renderTag($modifier, $tag_name, $context);
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
	protected function renderTag($modifier, $tag_name, &$context) {
		switch ($modifier) {
			case '#':
				if ($this->throwSectionExceptions) {
					throw new MustacheException('Unclosed section: ' . $tag_name, MustacheException::UNCLOSED_SECTION);
				} else {
					return '';
				}
				break;
			case '/':
				if ($this->throwSectionExceptions) {
					throw new MustacheException('Unexpected close section: ' . $tag_name, MustacheException::UNEXPECTED_CLOSE_SECTION);
				} else {
					return '';
				}
				break;
			case '=':
				return $this->changeDelimiter($tag_name, $context);
				break;
			case '!':
				return $this->renderComment($tag_name, $context);
				break;
			case '>':
				return $this->renderPartial($tag_name, $context);
				break;
			case '{':
			case '&':
				return $this->renderUnescaped($tag_name, $context);
				break;
			case '':
			default:
				return $this->renderEscaped($tag_name, $context);
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
	protected function renderEscaped($tag_name, &$context) {
		return htmlentities($this->getVariable($tag_name, $context), null, $this->charset);
	}

	/**
	 * Render a comment (i.e. return an empty string).
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param array $context
	 * @return string
	 */
	protected function renderComment($tag_name, &$context) {
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
	protected function renderUnescaped($tag_name, &$context) {
		return $this->getVariable($tag_name, $context);
	}

	/**
	 * Render the requested partial.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param array $context
	 * @return string
	 */
	protected function renderPartial($tag_name, &$context) {
		$view = new self($this->getPartial($tag_name), $context, $this->partials);
		$view->otag = $this->otag;
		$view->ctag = $this->ctag;
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
	protected function changeDelimiter($tag_name, &$context) {
		$tags = explode(' ', $tag_name);
		$this->otag = $tags[0];
		$this->ctag = $tags[1];

		$otag  = $this->prepareRegEx($this->otag);
		$ctag  = $this->prepareRegEx($this->ctag);
		$this->tagRegEx = '/' . $otag . "(#|\/|=|!|>|\\{|&)?([^\/#\^]+?)\\1?" . $ctag . "+/";
		return '';
	}


	/**
	 * Prepare a new context reference array.
	 *
	 * This is used to create context arrays for iterable blocks.
	 *
	 * @access protected
	 * @param array $context
	 * @param mixed $local_context
	 * @return void
	 */
	protected function getContext(&$context, &$local_context) {
		$ret = array();
		$ret[] =& $local_context;
		foreach ($context as $view) {
			$ret[] =& $view;
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
	protected function getVariable($tag_name, &$context) {
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

		if ($this->throwVariableExceptions) {
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
	protected function getPartial($tag_name) {
		if (is_array($this->partials) && isset($this->partials[$tag_name])) {
			return $this->partials[$tag_name];
		}

		if ($this->throwPartialExceptions) {
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
	protected function varIsIterable($var) {
		return is_object($var) || (is_array($var) && !array_diff_key($var, array_keys(array_keys($var))));
	}

	/**
	 * Prepare a string to be used in a regular expression.
	 *
	 * @access protected
	 * @param string $str
	 * @return string
	 */
	protected function prepareRegEx($str) {
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
	// without a corresponding {{#section}}.
	const UNEXPECTED_CLOSE_SECTION = 2;

	// An UNKNOWN_PARTIAL exception is thrown whenever a {{>partial}} tag appears
	// with no associated partial.
	const UNKNOWN_PARTIAL          = 3;

}