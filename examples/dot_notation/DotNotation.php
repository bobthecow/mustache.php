<?php

/**
 * DotNotation example class. Uses DOT_NOTATION pragma.
 *
 * @extends Mustache
 */
class DotNotation extends Mustache {
	public $person = array(
		'name' => array('first' => 'Chris', 'last' => 'Firescythe'),
		'age' => 24,
		'hobbies' => array('Cycling', 'Fishing'),
		'hometown' => array(
			'city' => 'Cincinnati',
			'state' => 'OH',
		),
	);

	public $normal = 'Normal';
}
