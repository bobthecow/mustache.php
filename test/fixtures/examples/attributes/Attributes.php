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

    public $bar = [
        ['label' => 'thing'],
        ['label' => 'other_thing'],
    ];

    public $bin = [
        'one',
        'two',
    ];
}
