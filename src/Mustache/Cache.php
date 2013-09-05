<?php

interface Mustache_Cache
{
    public function get($key);
    public function put($key, $value);
}
