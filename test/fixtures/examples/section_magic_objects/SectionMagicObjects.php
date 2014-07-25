<?php

class SectionMagicObjects
{
    public $start = "It worked the first time.";

    public function middle()
    {
        return new MagicObject();
    }

    public $final = "Then, surprisingly, it worked the final time.";
}

class MagicObject
{
    protected $_data = array(
        'foo' => 'And it worked the second time.',
        'bar' => 'As well as the third.'
    );

    public function __get($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    public function __isset($key)
    {
        return isset($this->_data[$key]);
    }
}
