<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test;

use Mustache\Mustache;
use Mustache\Buffer;

/**
 * @group unit
 */
class BufferTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getConstructorArgs
	 */
	public function testConstructor($indent, $charset) {
		$buffer = new Buffer($indent, $charset);
		$this->assertEquals($indent, $buffer->getIndent());
		$this->assertEquals($charset, $buffer->getCharset());
	}

	public function getConstructorArgs() {
		return array(
			array('',       'UTF-8'),
			array('    ',   'ISO-8859-1'),
			array("\t\t\t", 'Shift_JIS'),
		);
	}

	public function testWrite() {
		$buffer = new Buffer;
		$this->assertEquals('', $buffer->flush());

		$buffer->writeLine();
		$buffer->writeLine();
		$buffer->writeLine();
		$this->assertEquals("\n\n\n", $buffer->flush());
		$this->assertEquals('', $buffer->flush());

		$buffer->write('foo');
		$buffer->write('bar');
		$buffer->writeLine();
		$buffer->write('baz');
		$this->assertEquals("foobar\nbaz", $buffer->flush());
		$this->assertEquals('', $buffer->flush());

		$indent = "\t\t";
		$buffer->setIndent($indent);
		$buffer->writeText('foo');
		$buffer->writeLine();
		$buffer->writeText('bar');
		$this->assertEquals("\t\tfoo\n\t\tbar", $buffer->flush());
		$this->assertEquals('', $buffer->flush());
	}

	/**
	 * @dataProvider getEscapeAndIndent
	 */
	public function testEscapingAndIndenting($text, $escape, $indent, $whitespace, $expected) {
		$buffer = new Buffer;
		$buffer->setIndent($whitespace);

		$buffer->write($text, $indent, $escape);
		$this->assertEquals($expected, $buffer->flush());
	}

	public function getEscapeAndIndent() {
		return array(
			array('> "fun & games" <', false, false, "\t", '> "fun & games" <'),
			array('> "fun & games" <', true,  false, "\t", '&gt; &quot;fun &amp; games&quot; &lt;'),
			array('> "fun & games" <', true,  true,  "\t", "\t&gt; &quot;fun &amp; games&quot; &lt;"),
			array('> "fun & games" <', false, true,  "\t", "\t> \"fun & games\" <"),
		);
	}

	public function testChangeIndent() {
		$indent = "\t\t";
		$buffer = new Buffer($indent);
		$this->assertEquals($indent, $buffer->getIndent());

		$indent = "";
		$buffer->setIndent($indent);
		$this->assertEquals($indent, $buffer->getIndent());

		$indent = " ";
		$buffer->setIndent($indent);
		$this->assertEquals($indent, $buffer->getIndent());
	}

}
