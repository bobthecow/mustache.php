<?php

class I18n extends Mustache {

    // Variable to be interpolated
    public $name = 'Bob';

    // Add a {{#__}} lambda for i18n
    public $__ = array(__CLASS__, '__trans');

    // A *very* small i18n dictionary :)
    private $dictionary = array(
        'Hello.' => 'Hola.',
        'My name is {{ name }}.' => 'Me llamo {{ name }}.',
    );

    public function __trans($text) {
        return isset($this->dictionary[$text]) ? $this->dictionary[$text] : $text;
    }
}