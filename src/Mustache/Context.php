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
 * Mustache Template rendering Context.
 */
class Mustache_Context {
	private $stack = array();

	/**
	 * Mustache rendering Context constructor.
	 *
	 * @param mixed $context Default rendering context (default: null)
	 */
	public function __construct($context = null) {
		if ($context !== null) {
			$this->stack = array($context);
		}
	}

	/**
	 * Helper function to test whether a value is 'truthy'.
	 *
	 * @param mixed $value
	 *
	 * @return boolean True if the value is 'truthy'
	 */
	public function isTruthy($value) {
		return !empty($value);
	}

	/**
	 * Higher order sections helper: tests whether a value is a valid callback.
	 *
	 * In Mustache.php, a variable is considered 'callable' if the variable is:
	 *
	 *  1. An anonymous function.
	 *  2. An object and the name of a public function, e.g. `array($someObject, 'methodName')`
	 *  3. A class name and the name of a public static function, e.g. `array('SomeClass', 'methodName')`
	 *
	 * Note that this specifically excludes strings, which PHP would normally consider 'callable'.
	 *
	 * @param mixed $value
	 *
	 * @return boolean True if the value is 'callable'
	 */
	public function isCallable($value) {
		return !is_string($value) && is_callable($value);
	}

	/**
	 * Tests whether a value should be iterated over (e.g. in a section context).
	 *
	 * In most languages there are two distinct array types: list and hash (or whatever you want to call them). Lists
	 * should be iterated, hashes should be treated as objects. Mustache follows this paradigm for Ruby, Javascript,
	 * Java, Python, etc.
	 *
	 * PHP, however, treats lists and hashes as one primitive type: array. So Mustache.php needs a way to distinguish
	 * between between a list of things (numeric, normalized array) and a set of variables to be used as section context
	 * (associative array). In other words, this will be iterated over:
	 *
	 *     $items = array(
	 *         array('name' => 'foo'),
	 *         array('name' => 'bar'),
	 *         array('name' => 'baz'),
	 *     );
	 *
	 * ... but this will be used as a section context block:
	 *
	 *     $items = array(
	 *         1        => array('name' => 'foo'),
	 *         'banana' => array('name' => 'bar'),
	 *         42       => array('name' => 'baz'),
	 *     );
	 *
	 * @param mixed $value
	 *
	 * @return boolean True if the value is 'iterable'
	 */
	public function isIterable($value) {
		if (is_object($value)) {
			return $value instanceof Traversable;
		} elseif (is_array($value)) {
			return !array_diff_key($value, array_keys(array_keys($value)));
		}

		return false;
	}

	/**
	 * Push a new Context frame onto the stack.
	 *
	 * @param mixed $value Object or array to use for context
	 */
	public function push($value) {
		array_push($this->stack, $value);
	}

	/**
	 * Pop the last Context frame from the stack.
	 *
	 * @return mixed Last Context frame (object or array)
	 */
	public function pop() {
		return array_pop($this->stack);
	}

	/**
	 * Get the last Context frame.
	 *
	 * @return mixed Last Context frame (object or array)
	 */
	public function last() {
		return end($this->stack);
	}

	/**
	 * Find a variable in the Context stack.
	 *
	 * Starting with the last Context frame (the context of the innermost section), and working back to the top-level
	 * rendering context, look for a variable with the given name:
	 *
	 *  * If the Context frame is an associative array which contains the key $id, returns the value of that element.
	 *  * If the Context frame is an object, this will check first for a public method, then a public property named
	 *    $id. Failing both of these, it will try `__isset` and `__get` magic methods.
	 *  * If a value named $id is not found in any Context frame, returns an empty string.
	 *
	 * @param string $id Variable name
	 *
	 * @return mixed Variable value, or '' if not found
	 */
	public function find($id) {
		return $this->findVariableInStack($id, $this->stack);
	}

	/**
	 * Find a 'dot notation' variable in the Context stack.
	 *
	 * Note that dot notation traversal bubbles through scope differently than the regular find method. After finding
	 * the initial chunk of the dotted name, each subsequent chunk is searched for only within the value of the previous
	 * result. For example, given the following context stack:
	 *
	 *     $data = array(
	 *         'name' => 'Fred',
	 *         'child' => array(
	 *             'name' => 'Bob'
	 *         ),
	 *     );
	 *
	 * ... and the Mustache following template:
	 *
	 *     {{ child.name }}
	 *
	 * ... the `name` value is only searched for within the `child` value of the global Context, not within parent
	 * Context frames.
	 *
	 * @param string $id Dotted variable selector
	 *
	 * @return mixed Variable value, or '' if not found
	 */
	public function findDot($id) {
		$chunks = explode('.', $id);
		$first  = array_shift($chunks);
		$value  = $this->findVariableInStack($first, $this->stack);

		foreach ($chunks as $chunk) {
			if ($value === '') {
				return $value;
			}

			$value = $this->findVariableInStack($chunk, array($value));
		}

		return $value;
	}

	/**
	 * Helper function to find a variable in the Context stack.
	 *
	 * @see Mustache_Context::find
	 *
	 * @param string $id    Variable name
	 * @param array  $stack Context stack
	 *
	 * @return mixed Variable value, or '' if not found
	 */
	private function findVariableInStack($id, array $stack) {
		for ($i = count($stack) - 1; $i >= 0; $i--) {
			if (is_object($stack[$i])) {
				if (method_exists($stack[$i], $id)) {
					return $stack[$i]->$id();
				} elseif (isset($stack[$i]->$id)) {
					return $stack[$i]->$id;
				}
			} elseif (is_array($stack[$i]) && array_key_exists($id, $stack[$i])) {
				return $stack[$i][$id];
			}
		}

		return '';
	}
}
