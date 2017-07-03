<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2017 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class DotNotation
{
    public $person = array(
        'name'     => array('first' => 'Chris', 'last' => 'Firescythe'),
        'age'      => 24,
        'hometown' => array(
            'city'  => 'Cincinnati',
            'state' => 'OH',
        ),
    );

    public $normal = 'Normal';
}
