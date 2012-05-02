<?php

class RecursivePartials
{
    public $name  = 'George';
    public $child = array(
        'name'  => 'Dan',
        'child' => array(
            'name'  => 'Justin',
            'child' => false,
        )
    );
}
