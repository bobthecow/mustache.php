<?php

class ChildContext
{
    public $parent = array(
        'child' => 'child works',
    );

    public $grandparent = array(
        'parent' => array(
            'child' => 'grandchild works',
        ),
    );
}
