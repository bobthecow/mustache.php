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
 * Mustache Compiler class.
 *
 * This class is responsible for turning a Mustache token parse tree into normal PHP source code.
 */
class Mustache_Compiler {

	/**
	 * Compile a Mustache token parse tree into PHP source code.
	 *
	 * @param string $source Mustache Template source code
	 * @param string $tree   Parse tree of Mustache tokens
	 * @param string $name   Mustache Template class name
	 *
	 * @return string Generated PHP source code
	 */
	public function compile($source, array $tree, $name) {
		$this->source = $source;

		return $this->writeCode($tree, $name);
	}

	/**
	 * Helper function for walking the Mustache token parse tree.
	 *
	 * @throws InvalidArgumentException upon encountering unknown token types.
	 *
	 * @param array $tree  Parse tree of Mustache tokens
	 * @param int   $level (default: 0)
	 *
	 * @return string Generated PHP source code;
	 */
	private function walk(array $tree, $level = 0) {
		$code = '';
		$level++;
		foreach ($tree as $node) {
			switch (is_string($node) ? 'text' : $node[Mustache_Tokenizer::TAG]) {
				case '#':
					$code .= $this->section(
						$node[Mustache_Tokenizer::NODES],
						$node[Mustache_Tokenizer::NAME],
						$node[Mustache_Tokenizer::INDEX],
						$node[Mustache_Tokenizer::END],
						$node[Mustache_Tokenizer::OTAG],
						$node[Mustache_Tokenizer::CTAG],
						$level
					);
					break;

				case '^':
					$code .= $this->invertedSection(
						$node[Mustache_Tokenizer::NODES],
						$node[Mustache_Tokenizer::NAME],
						$level
					);
					break;

				case '<':
				case '>':
					$code .= $this->partial(
						$node[Mustache_Tokenizer::NAME],
						isset($node[Mustache_Tokenizer::INDENT]) ? $node[Mustache_Tokenizer::INDENT] : '',
						$level
					);
					break;

				case '{':
				case '&':
					$code .= $this->variable($node[Mustache_Tokenizer::NAME], false, $level);
					break;

				case '!':
					break;

				case '_v':
					$code .= $this->variable($node[Mustache_Tokenizer::NAME], true, $level);
					break;


				case 'text':
					$code .= $this->text($node, $level);
					break;

				default:
					throw new InvalidArgumentException('Unknown node type: '.json_encode($node));
			}
		}

		return $code;
	}

	const KLASS = '<?php

		class %s extends Mustache_Template {
			public function renderInternal(Mustache_Context $context, $indent = \'\') {
				$mustache = $this->mustache;
				$buffer = new Mustache_Buffer($indent, $mustache->getCharset());
		%s

				return $buffer->flush();
			}
		}';

	/**
	 * Generate Mustache Template class PHP source.
	 *
	 * @param array  $tree Parse tree of Mustache tokens
	 * @param string $name Mustache Template class name
	 *
	 * @return string Generated PHP source code
	 */
	private function writeCode($tree, $name) {
		return sprintf($this->prepare(self::KLASS, 0, false), $name, $this->walk($tree), $name);
	}

	const SECTION = '
		// %s section
		$value = $context->%s(%s);
		if ($context->isCallable($value)) {
			$source = %s;
			$buffer->write(
				$mustache
					->loadLambda((string) call_user_func($value, $source)%s)
					->renderInternal($context, $buffer->getIndent())
			);
		} elseif ($context->isTruthy($value)) {
			$values = $context->isIterable($value) ? $value : array($value);
			foreach ($values as $value) {
				$context->push($value);%s
				$context->pop();
			}
		}';

	/**
	 * Generate Mustache Template section PHP source.
	 *
	 * @param array  $nodes Array of child tokens
	 * @param string $id    Section name
	 * @param int    $start Section start offset
	 * @param int    $end   Section end offset
	 * @param string $otag  Current Mustache opening tag
	 * @param string $ctag  Current Mustache closing tag
	 * @param int    $level
	 *
	 * @return string Generated section PHP source code
	 */
	private function section($nodes, $id, $start, $end, $otag, $ctag, $level) {
		$method = $this->getFindMethod($id);
		$id     = var_export($id, true);
		$source = var_export(substr($this->source, $start, $end - $start), true);

		if ($otag !== '{{' || $ctag !== '}}') {
			$delims = ', '.var_export(sprintf('{{= %s %s =}}', $otag, $ctag), true);
		} else {
			$delims = '';
		}

		return sprintf($this->prepare(self::SECTION, $level), $id, $method, $id, $source, $delims, $this->walk($nodes, $level + 1));
	}

	const INVERTED_SECTION = '
		// %s inverted section
		if (!$context->isTruthy($context->%s(%s))) {
			%s
		}';

	/**
	 * Generate Mustache Template inverted section PHP source.
	 *
	 * @param array  $nodes Array of child tokens
	 * @param string $id    Section name
	 * @param int    $level
	 *
	 * @return string Generated inverted section PHP source code
	 */
	private function invertedSection($nodes, $id, $level) {
		$method = $this->getFindMethod($id);
		$id     = var_export($id, true);

		return sprintf($this->prepare(self::INVERTED_SECTION, $level), $id, $method, $id, $this->walk($nodes, $level));
	}

	const PARTIAL = '$buffer->write($mustache->loadPartial(%s)->renderInternal($context, %s));';

	/**
	 * Generate Mustache Template partial call PHP source.
	 *
	 * @param string $id     Partial name
	 * @param string $indent Whitespace indent to apply to partial
	 * @param int    $level
	 *
	 * @return string Generated partial call PHP source code
	 */
	private function partial($id, $indent, $level) {
		return sprintf(
			$this->prepare(self::PARTIAL, $level),
			var_export($id, true),
			var_export($indent, true)
		);
	}

	const VARIABLE = '
		$value = $context->%s(%s);
		if ($context->isCallable($value)) {
			$value = $mustache
				->loadLambda((string) call_user_func($value))
				->renderInternal($context, $buffer->getIndent());
		}
		$buffer->writeText($value, %s);
	';

	/**
	 * Generate Mustache Template variable interpolation PHP source.
	 *
	 * @param string  $id     Variable name
	 * @param boolean $escape Escape the variable value for output?
	 * @param int     $level
	 *
	 * @return string Generated variable interpolation PHP source
	 */
	private function variable($id, $escape, $level) {
		$method = $this->getFindMethod($id);
		$id     = ($method !== 'last') ? var_export($id, true) : '';
		$escape = $escape ? 'true' : 'false';

		return sprintf($this->prepare(self::VARIABLE, $level), $method, $id, $escape);
	}

	const LINE = '$buffer->writeLine();';
	const TEXT = '$buffer->writeText(%s);';

	/**
	 * Generate Mustache Template output Buffer call PHP source.
	 *
	 * @param string $text
	 * @param int    $level
	 *
	 * @return string Generated output Buffer call PHP source
	 */
	private function text($text, $level) {
		if ($text === "\n") {
			return $this->prepare(self::LINE, $level);
		} else {
			return sprintf($this->prepare(self::TEXT, $level), var_export($text, true));
		}
	}

	/**
	 * Prepare PHP source code snippet for output.
	 *
	 * @param string  $text
	 * @param int     $bonus          Additional indent level (default: 0)
	 * @param boolean $prependNewline Prepend a newline to the snippet? (default: true)
	 *
	 * @return string PHP source code snippet
	 */
	private function prepare($text, $bonus = 0, $prependNewline = true) {
		$text = ($prependNewline ? "\n" : '').trim($text);
		if ($prependNewline) {
			$bonus++;
		}

		return preg_replace("/\n(\t\t)?/", "\n".str_repeat("\t", $bonus), $text);
	}

	/**
	 * Select the appropriate Context `find` method for a given $id.
	 *
	 * The return value will be one of `find`, `findDot` or `last`.
	 *
	 * @see Mustache_Context::find
	 * @see Mustache_Context::findDot
	 * @see Mustache_Context::last
	 *
	 * @param string $id Variable name
	 *
	 * @return string `find` method name
	 */
	private function getFindMethod($id) {
		if ($id === '.') {
			return 'last';
		} elseif (strpos($id, '.') === false) {
			return 'find';
		} else {
			return 'findDot';
		}
	}
}
