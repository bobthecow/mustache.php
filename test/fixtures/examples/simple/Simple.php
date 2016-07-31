<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2016 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Simple
{
    public $name = 'Chris';
    public $value = 10000;

    public function taxed_value()
    {
        return $this->value - ($this->value * 0.4);
    }

    public $in_ca = true;
}
