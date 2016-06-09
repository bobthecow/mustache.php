<?php

class Attributes
{
    // public function foo()
    // {
    //     // You can't use class methods as callables.
    //     // To pass in attrs, we need to return a callable.
    //     return function ($attrs) {
    //         return $attrs['bar'];
    //     };
    // }

    public $bar = array(
        array('label' => 'thing'),
        array('label' => 'other_thing'),
    );

    public $bin = array(
        'one',
        'two',
    );
}
