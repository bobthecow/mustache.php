<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2017 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Whitespace test for tag names.
 *
 * Per http://github.com/janl/mustache.js/issues/issue/34/#comment_244396
 * tags should strip leading and trailing whitespace in key names.
 *
 * `{{> tag }}` and `{{> tag}}` and `{{>tag}}` should all be equivalent.
 */
class Whitespace
{
    public $foo = 'alpha';

    public $bar = 'beta';

    public function baz()
    {
        return 'gamma';
    }

    public function qux()
    {
        return array(
            array('key with space' => 'A'),
            array('key with space' => 'B'),
            array('key with space' => 'C'),
            array('key with space' => 'D'),
            array('key with space' => 'E'),
            array('key with space' => 'F'),
            array('key with space' => 'G'),
        );
    }
}
