<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2016 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class ChildContext
{
    public $parent = array(
        'child' => 'child works',
    );

    public $grandparent = array(
        'parent' => array(
            'child' => 'grandchild works',
        ),
    );
}
