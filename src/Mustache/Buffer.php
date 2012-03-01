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
 * Mustache output Buffer class.
 *
 * Buffer instances are used by Mustache Templates for collecting output during rendering
 */
class Mustache_Buffer {
	private $buffer  = '';
	private $indent  = '';
	private $charset = 'UTF-8';

	/**
	 * Mustache Buffer constructor.
	 *
	 * @param string $indent  Initial indent level for all lines of this buffer (default: '')
	 * @param string $charset Override the character set used by `htmlspecialchars()` (default: 'UTF-8')
	 */
	public function __construct($indent = null, $charset = null) {
		if ($indent !== null) {
			$this->setIndent($indent);
		}

		if ($charset !== null) {
			$this->charset = $charset;
		}
	}

	/**
	 * Get the current indent level.
	 *
	 * @return string
	 */
	public function getIndent() {
		return $this->indent;
	}

	/**
	 * Set the buffer indent level.
	 *
	 * Each line output by this buffer will be prefixed by this whitespace. This is used when rendering
	 * partials and Lambda sections.
	 *
	 * @param string $indent
	 */
	public function setIndent($indent) {
		$this->indent = $indent;
	}

	/**
	 * Get the character set used when escaping values.
	 *
	 * @return string
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * Write a newline to the Buffer.
	 */
	public function writeLine() {
		$this->buffer .= "\n";
	}

	/**
	 * Output text to the Buffer.
	 *
	 * @see Mustache_Buffer::write
	 *
	 * @param string $text
	 * @param bool   $escape Escape this text with `htmlspecialchars()`? (default: false)
	 */
	public function writeText($text, $escape = false) {
		$this->write($text, true, $escape);
	}

	/**
	 * Add output to the Buffer.
	 *
	 * @param string $text
	 * @param bool   $indent Indent this line? (default: false)
	 * @param bool   $escape Escape this text with `htmlspecialchars()`? (default: false)
	 */
	public function write($text, $indent = false, $escape = false) {
		$text = (string) $text;

		if ($escape) {
			$text = $this->escape($text);
		}

		if ($indent) {
			$this->buffer .= $this->indent . $text;
		} else {
			$this->buffer .= $text;
		}
	}

	/**
	 * Flush the contents of the Buffer.
	 *
	 * Resets the buffer and returns the current contents.
	 *
	 * @return string
	 */
	public function flush() {
		$buffer = $this->buffer;
		$this->buffer = '';

		return $buffer;
	}

	/**
	 * Helper function to escape text.
	 *
	 * Uses the Buffer's character set (default 'UTF-8', passed as the second argument to `__construct`).
	 *
	 * @see htmlspecialchars
	 *
	 * @param string $text
	 *
	 * @return string Escaped text
	 */
	private function escape($text) {
		return htmlspecialchars($text, ENT_COMPAT, $this->charset);
	}
}
