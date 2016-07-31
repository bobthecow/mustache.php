<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2016 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class SectionsNested
{
    public $name = 'Little Mac';

    public function enemies()
    {
        return array(
            array(
                'name'    => 'Von Kaiser',
                'enemies' => array(
                    array('name' => 'Super Macho Man'),
                    array('name' => 'Piston Honda'),
                    array('name' => 'Mr. Sandman'),
                ),
            ),
            array(
                'name'    => 'Mike Tyson',
                'enemies' => array(
                    array('name' => 'Soda Popinski'),
                    array('name' => 'King Hippo'),
                    array('name' => 'Great Tiger'),
                    array('name' => 'Glass Joe'),
                ),
            ),
            array(
                'name'    => 'Don Flamenco',
                'enemies' => array(
                    array('name' => 'Bald Bull'),
                ),
            ),
        );
    }
}
