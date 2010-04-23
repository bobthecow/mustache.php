<?php

/**
 * DotNotation example class. Uses DOT_NOTATION pragma.
 * 
 * @extends Mustache
 */
class DotNotation extends Mustache {
	public $foo = array(
		'bar' => array(
			'baz' => 'Qux',
		)
	);
}
