<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2017 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class SectionObjects
{
    public $start = 'It worked the first time.';

    public function middle()
    {
        return new SectionObject();
    }

    public $final = 'Then, surprisingly, it worked the final time.';
}

class SectionObject
{
    public $foo = 'And it worked the second time.';
    public $bar = 'As well as the third.';
}
