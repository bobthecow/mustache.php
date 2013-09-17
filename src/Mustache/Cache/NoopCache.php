<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Mustache_Cache_NoopCache extends Mustache_Cache_AbstractCache
{
    public function load($key)
    {
        return false;
    }

    public function cache($key, $compiled)
    {
        $this->log(
            Mustache_Logger::WARNING,
            'Template cache disabled, evaluating "{className}" class at runtime',
            array('className' => $key)
        );
        eval("?>".$compiled);
    }
}
