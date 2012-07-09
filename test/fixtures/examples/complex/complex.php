<?php

class Complex
{
    public $header = 'Colors';

    public $item = array(
        array('name' => 'red', 'current' => true, 'url' => '#Red'),
        array('name' => 'green', 'current' => false, 'url' => '#Green'),
        array('name' => 'blue', 'current' => false, 'url' => '#Blue'),
    );

    public function notEmpty()
    {
        return !($this->isEmpty());
    }

    public function isEmpty()
    {
        return count($this->item) === 0;
    }
}
