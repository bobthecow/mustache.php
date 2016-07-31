<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2016 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class I18n
{
    // Variable to be interpolated
    public $name = 'Bob';

    // Add a {{#__}} lambda for i18n
    public $__ = array(__CLASS__, '__trans');

    // A *very* small i18n dictionary :)
    private static $dictionary = array(
        'Hello.'                 => 'Hola.',
        'My name is {{ name }}.' => 'Me llamo {{ name }}.',
    );

    public static function __trans($text)
    {
        return isset(self::$dictionary[$text]) ? self::$dictionary[$text] : $text;
    }
}
