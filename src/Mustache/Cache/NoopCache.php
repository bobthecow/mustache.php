<?php

class Mustache_Cache_NoopCache implements Mustache_Cache
{
    public function get($key) { return null; }
    public function put($key, $value) {}
}
