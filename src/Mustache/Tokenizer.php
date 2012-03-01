<?php

namespace Mustache;

/**
 * Mustache Tokenizer class.
 *
 * This class is responsible for turning raw template source into a set of Mustache tokens.
 */
class Tokenizer {

	// Finite state machine states
	const IN_TEXT     = 0;
	const IN_TAG_TYPE = 1;
	const IN_TAG      = 2;

	// Token types
	const T_SECTION      = 1;
	const T_INVERTED     = 2;
	const T_END_SECTION  = 3;
	const T_COMMENT      = 4;
	const T_PARTIAL      = 5;
	const T_PARTIAL_2    = 6;
	const T_DELIM_CHANGE = 7;
	const T_ESCAPED      = 8;
	const T_UNESCAPED    = 9;
	const T_UNESCAPED_2  = 10;

	// Token types map
	private static $tagTypes = array(
		'#'  => self::T_SECTION,
		'^'  => self::T_INVERTED,
		'/'  => self::T_END_SECTION,
		'!'  => self::T_COMMENT,
		'>'  => self::T_PARTIAL,
		'<'  => self::T_PARTIAL_2,
		'='  => self::T_DELIM_CHANGE,
		'_v' => self::T_ESCAPED,
		'{'  => self::T_UNESCAPED,
		'&'  => self::T_UNESCAPED_2,
	);

	// Token properties
	const NODES  = 'nodes';
	const TAG    = 'tag';
	const NAME   = 'name';
	const OTAG   = 'otag';
	const CTAG   = 'ctag';
	const INDEX  = 'index';
	const END    = 'end';
	const INDENT = 'indent';

	private $state;
	private $tagType;
	private $tag;
	private $buf;
	private $tokens;
	private $seenTag;
	private $lineStart;
	private $otag;
	private $ctag;

	/**
	 * Scan and tokenize template source.
	 *
	 * @param string $text       Mustache template source to tokenize
	 * @param string $delimiters Optionally, pass initial opening and closing delimiters (default: null)
	 *
	 * @return array Set of Mustache tokens
	 */
	public function scan($text, $delimiters = null) {
		$this->reset();

		if ($delimiters = trim($delimiters)) {
			list($otag, $ctag) = explode(' ', $delimiters);
			$this->otag = $otag;
			$this->ctag = $ctag;
		}

		$len = strlen($text);
		for ($i = 0; $i < $len; $i++) {
			switch ($this->state) {
				case self::IN_TEXT:
					if ($this->tagChange($this->otag, $text, $i)) {
						$i--;
						$this->flushBuffer();
						$this->state = self::IN_TAG_TYPE;
					} else {
						if ($text[$i] == "\n") {
							$this->filterLine();
						} else {
							$this->buffer .= $text[$i];
						}
					}
					break;

				case self::IN_TAG_TYPE:
					$i += strlen($this->otag) - 1;
					$tag = isset(self::$tagTypes[$text[$i + 1]]) ? self::$tagTypes[$text[$i + 1]] : null;
					$this->tagType = $tag ? $text[$i + 1] : '_v';
					if ($this->tagType === '=') {
						$i = $this->changeDelimiters($text, $i);
						$this->state = self::IN_TEXT;
					} else {
						if ($tag) {
							$i++;
						}
						$this->state = self::IN_TAG;
					}
					$this->seenTag = $i;
					break;

				default:
					if ($this->tagChange($this->ctag, $text, $i)) {
						$this->tokens[] = array(
							self::TAG   => $this->tagType,
							self::NAME  => trim($this->buffer),
							self::OTAG  => $this->otag,
							self::CTAG  => $this->ctag,
							self::INDEX => ($this->tagType == '/') ? $this->seenTag - strlen($this->otag) : $i + strlen($this->ctag)
						);

						$this->buffer = '';
						$i += strlen($this->ctag) - 1;
						$this->state = self::IN_TEXT;
						if ($this->tagType == '{') {
							if ($this->ctag == '}}') {
								$i++;
							} else {
								$this->cleanTripleStache($this->tokens[count($this->tokens) - 1]);
							}
						}
					} else {
						$this->buffer .= $text[$i];
					}
					break;
			}
		}

		$this->filterLine(true);

		return $this->tokens;
	}

	/**
	 * Helper function to reset tokenizer internal state.
	 */
	private function reset() {
		$this->state     = self::IN_TEXT;
		$this->tagType   = null;
		$this->tag       = null;
		$this->buffer    = '';
		$this->tokens    = array();
		$this->seenTag   = false;
		$this->lineStart = 0;
		$this->otag      = '{{';
		$this->ctag      = '}}';
	}

	/**
	 * Flush the current buffer to a token.
	 */
	private function flushBuffer() {
		if (!empty($this->buffer)) {
			$this->tokens[] = $this->buffer;
			$this->buffer   = '';
		}
	}

	/**
	 * Test whether the current line is entirely made up of whitespace.
	 *
	 * @return boolean True if the current line is all whitespace
	 */
	private function lineIsWhitespace() {
		$tokensCount = count($this->tokens);
		for ($j = $this->lineStart; $j < $tokensCount; $j++) {
			$token = $this->tokens[$j];
			if (is_array($token) && isset(self::$tagTypes[$token[self::TAG]])) {
				if (self::$tagTypes[$token[self::TAG]] >= self::T_ESCAPED) {
					return false;
				}
			} elseif (is_string($token)) {
				if (preg_match('/\S/', $token)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Filter out whitespace-only lines and store indent levels for partials.
	 *
	 * @param bool $noNewLine Suppress the newline? (default: false)
	 */
	private function filterLine($noNewLine = false) {
		$this->flushBuffer();
		if ($this->seenTag && $this->lineIsWhitespace()) {
			$tokensCount = count($this->tokens);
			for ($j = $this->lineStart; $j < $tokensCount; $j++) {
				if (!is_array($this->tokens[$j])) {
					if (isset($this->tokens[$j+1]) && is_array($this->tokens[$j+1]) && $this->tokens[$j+1][self::TAG] == '>') {
						$this->tokens[$j+1][self::INDENT] = (string) $this->tokens[$j];
					}

					$this->tokens[$j] = null;
				}
			}
		} elseif (!$noNewLine) {
			$this->tokens[] = "\n";
		}

		$this->seenTag   = false;
		$this->lineStart = count($this->tokens);
	}

	/**
	 * Change the current Mustache delimiters. Set new `otag` and `ctag` values.
	 *
	 * @param string $text  Mustache template source
	 * @param int    $index Current tokenizer index
	 *
	 * @return int New index value
	 */
	private function changeDelimiters($text, $index) {
		$startIndex = strpos($text, '=', $index) + 1;
		$close      = '='.$this->ctag;
		$closeIndex = strpos($text, $close, $index);

		list($otag, $ctag) = explode(' ', trim(substr($text, $startIndex, $closeIndex - $startIndex)));
		$this->otag = $otag;
		$this->ctag = $ctag;

		return $closeIndex + strlen($close) - 1;
	}

	/**
	 * Clean up `{{{ tripleStache }}}` style tokens.
	 *
	 * @param array &$token
	 */
	private function cleanTripleStache(&$token) {
		if (substr($token[self::NAME], -1) === '}') {
			$token[self::NAME] = trim(substr($token[self::NAME], 0, -1));
		}
	}

	/**
	 * Test whether it's time to change tags.
	 *
	 * @param string $tag   Current tag name
	 * @param string $text  Mustache template source
	 * @param int    $index Current tokenizer index
	 *
	 * @return boolean True if this is a closing section tag
	 */
	private function tagChange($tag, $text, $index) {
		return substr($text, $index, strlen($tag)) === $tag;
	}
}
