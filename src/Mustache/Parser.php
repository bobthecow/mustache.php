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

/**
 * Mustache Parser class.
 *
 * This class is responsible for turning a set of Mustache tokens into a parse tree.
 */
class Parser {

	/**
	 * Process an array of Mustache tokens and convert them into a parse tree.
	 *
	 * @param array $tokens Set of Mustache tokens
	 *
	 * @return array Mustache token parse tree
	 */
	public function parse(array $tokens = array()) {
		return $this->buildTree(new \ArrayIterator($tokens));
	}

	/**
	 * Helper method for recursively building a parse tree.
	 *
	 * @throws \LogicException when nesting errors or mismatched section tags are encountered.
	 *
	 * @param \ArrayIterator $tokens Stream of Mustache tokens
	 * @param array          $parent Parent token (default: null)
	 *
	 * @return array Mustache Token parse tree
	 */
	private function buildTree(\ArrayIterator $tokens, array $parent = null) {
		$nodes = array();

		do {
			$token = $tokens->current();
			$tokens->next();

			if ($token === null) {
				continue;
			} elseif (is_array($token)) {
				switch ($token[Tokenizer::TYPE]) {
					case Tokenizer::T_SECTION:
					case Tokenizer::T_INVERTED:
						$nodes[] = $this->buildTree($tokens, $token);
						break;

					case Tokenizer::T_END_SECTION:
						if (!isset($parent)) {
							throw new \LogicException('Unexpected closing tag: /'. $token[Tokenizer::NAME]);
						}

						if ($token[Tokenizer::NAME] !== $parent[Tokenizer::NAME]) {
							throw new \LogicException('Nesting error: ' . $parent[Tokenizer::NAME] . ' vs. ' . $token[Tokenizer::NAME]);
						}

						$parent[Tokenizer::END]   = $token[Tokenizer::INDEX];
						$parent[Tokenizer::NODES] = $nodes;

						return $parent;
						break;

					default:
						$nodes[] = $token;
						break;
				}
			} else {
				$nodes[] = $token;
			}

		} while ($tokens->valid());

		if (isset($parent)) {
			throw new \LogicException('Missing closing tag: ' . $parent[Tokenizer::NAME]);
		}

		return $nodes;
	}
}
