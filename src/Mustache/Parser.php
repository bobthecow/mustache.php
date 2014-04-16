<?php
namespace Mustache;

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Mustache Parser class.
 *
 * This class is responsible for turning a set of Mustache tokens into a parse tree.
 */
class Parser
{
    private $lineNum;
    private $lineTokens;

    /**
     * Process an array of Mustache tokens and convert them into a parse tree.
     *
     * @param array $tokens Set of Mustache tokens
     *
     * @return array Mustache token parse tree
     */
    public function parse(array $tokens = array())
    {
        $this->lineNum    = -1;
        $this->lineTokens = 0;

        return $this->buildTree($tokens);
    }

    /**
     * Helper method for recursively building a parse tree.
     *
     * @throws \Mustache\Exception\SyntaxException when nesting errors or mismatched section tags are encountered.
     *
     * @param array &$tokens Set of Mustache tokens
     * @param array $parent  Parent token (default: null)
     *
     * @return array Mustache Token parse tree
     */
    private function buildTree(array &$tokens, array $parent = null)
    {
        $nodes = array();

        while (!empty($tokens)) {
            $token = array_shift($tokens);

            if ($token[\Mustache\Tokenizer::LINE] === $this->lineNum) {
                $this->lineTokens++;
            } else {
                $this->lineNum    = $token[\Mustache\Tokenizer::LINE];
                $this->lineTokens = 0;
            }

            switch ($token[\Mustache\Tokenizer::TYPE]) {
                case \Mustache\Tokenizer::T_DELIM_CHANGE:
                    $this->clearStandaloneLines($nodes, $tokens);
                    break;

                case \Mustache\Tokenizer::T_SECTION:
                case \Mustache\Tokenizer::T_INVERTED:
                    $this->clearStandaloneLines($nodes, $tokens);
                    $nodes[] = $this->buildTree($tokens, $token);
                    break;

                case \Mustache\Tokenizer::T_END_SECTION:
                    if (!isset($parent)) {
                        $msg = sprintf(
                            'Unexpected closing tag: /%s on line %d',
                            $token[\Mustache\Tokenizer::NAME],
                            $token[\Mustache\Tokenizer::LINE]
                        );
                        throw new \Mustache\Exception\SyntaxException($msg, $token);
                    }

                    if ($token[\Mustache\Tokenizer::NAME] !== $parent[\Mustache\Tokenizer::NAME]) {
                        $msg = sprintf(
                            'Nesting error: %s (on line %d) vs. %s (on line %d)',
                            $parent[\Mustache\Tokenizer::NAME],
                            $parent[\Mustache\Tokenizer::LINE],
                            $token[\Mustache\Tokenizer::NAME],
                            $token[\Mustache\Tokenizer::LINE]
                        );
                        throw new \Mustache\Exception\SyntaxException($msg, $token);
                    }

                    $this->clearStandaloneLines($nodes, $tokens);
                    $parent[\Mustache\Tokenizer::END]   = $token[\Mustache\Tokenizer::INDEX];
                    $parent[\Mustache\Tokenizer::NODES] = $nodes;

                    return $parent;

                case \Mustache\Tokenizer::T_PARTIAL:
                case \Mustache\Tokenizer::T_PARTIAL_2:
                    // store the whitespace prefix for laters!
                    if ($indent = $this->clearStandaloneLines($nodes, $tokens)) {
                        $token[\Mustache\Tokenizer::INDENT] = $indent[\Mustache\Tokenizer::VALUE];
                    }
                    $nodes[] = $token;
                    break;

                case \Mustache\Tokenizer::T_PRAGMA:
                case \Mustache\Tokenizer::T_COMMENT:
                    $this->clearStandaloneLines($nodes, $tokens);
                    $nodes[] = $token;
                    break;

                default:
                    $nodes[] = $token;
                    break;
            }
        }

        if (isset($parent)) {
            $msg = sprintf(
                'Missing closing tag: %s opened on line %d',
                $parent[\Mustache\Tokenizer::NAME],
                $parent[\Mustache\Tokenizer::LINE]
            );
            throw new \Mustache\Exception\SyntaxException($msg, $parent);
        }

        return $nodes;
    }

    /**
     * Clear standalone line tokens.
     *
     * Returns a whitespace token for indenting partials, if applicable.
     *
     * @param array $nodes  Parsed nodes.
     * @param array $tokens Tokens to be parsed.
     *
     * @return array Resulting indent token, if any.
     */
    private function clearStandaloneLines(array &$nodes, array &$tokens)
    {
        if ($this->lineTokens > 1) {
            // this is the third or later node on this line, so it can't be standalone
            return;
        }

        $prev = null;
        if ($this->lineTokens === 1) {
            // this is the second node on this line, so it can't be standalone
            // unless the previous node is whitespace.
            if ($prev = end($nodes)) {
                if (!$this->tokenIsWhitespace($prev)) {
                    return;
                }
            }
        }

        if ($next = reset($tokens)) {
            // If we're on a new line, bail.
            if ($next[\Mustache\Tokenizer::LINE] !== $this->lineNum) {
                return;
            }

            // If the next token isn't whitespace, bail.
            if (!$this->tokenIsWhitespace($next)) {
                return;
            }

            if (count($tokens) !== 1) {
                // Unless it's the last token in the template, the next token
                // must end in newline for this to be standalone.
                if (substr($next[\Mustache\Tokenizer::VALUE], -1) !== "\n") {
                    return;
                }
            }

            // Discard the whitespace suffix
            array_shift($tokens);
        }

        if ($prev) {
            // Return the whitespace prefix, if any
            return array_pop($nodes);
        }
    }

    /**
     * Check whether token is a whitespace token.
     *
     * True if token type is T_TEXT and value is all whitespace characters.
     *
     * @param array $token
     *
     * @return boolean True if token is a whitespace token
     */
    private function tokenIsWhitespace(array $token)
    {
        if ($token[\Mustache\Tokenizer::TYPE] == \Mustache\Tokenizer::T_TEXT) {
            return preg_match('/^\s*$/', $token[\Mustache\Tokenizer::VALUE]);
        }

        return false;
    }
}
