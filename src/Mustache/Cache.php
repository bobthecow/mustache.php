<?php

interface Mustache_Cache
{
    public function load($key);
    public function cache($key, $value);
}
