<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2017 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class RecursivePartials
{
    public $name  = 'George';
    public $child = array(
        'name'  => 'Dan',
        'child' => array(
            'name'  => 'Justin',
            'child' => false,
        ),
    );
}
