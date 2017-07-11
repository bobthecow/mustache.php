<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2017 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class AnchoredDotNotation
{
    public $genres = array(
        array(
            'name'      => 'Punk',
            'subgenres' => array(
                array(
                    'name'      => 'Hardcore',
                    'subgenres' => array(
                        array(
                            'name'      => 'First wave of black metal',
                            'subgenres' => array(
                                array('name' => 'Norwegian black metal'),
                                array(
                                    'name'      => 'Death metal',
                                    'subgenres' => array(
                                        array(
                                            'name'      => 'Swedish death metal',
                                            'subgenres' => array(
                                                array('name' => 'New wave of American metal'),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'name'      => 'Thrash metal',
                            'subgenres' => array(
                                array('name' => 'Grindcore'),
                                array(
                                    'name'      => 'Metalcore',
                                    'subgenres' => array(
                                        array('name' => 'Nu metal'),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
    );
}
