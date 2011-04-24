<?php

/**
 * DotNotationArrayIndex example class. Uses DOT_NOTATION pragma.
 *
 * @extends Mustache
 */
class DotNotationArrayIndex extends Mustache {
	public $people = array(
    array('name' => 'John',
          'age' => 25),
    array('name' => 'Marie',
          'age' => 22)
    );
}
