<?php

class Mustache_Cache_NoopCache implements Mustache_Cache
{
    public function load($key)
    {
        return false;
    }

    public function cache($key, $compiled)
    {
        eval("?>".$compiled);
    }
}
