<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Mustache Cache in-memory implementation.
 *
 * In-memory implementation useful during development.
 * Not recommended for production use.
 */
class Mustache_Cache_NoopCache extends Mustache_Cache_AbstractCache
{
    /**
     * Loads nothing. Move along.
     *
     * @param  string $key
     * @return boolean
     */
    public function load($key)
    {
        return false;
    }

    /**
     * Loads the compiled Mustache Template class without caching.
     *
     * @param  string $key
     * @param  string $compiled
     * @return void
     */
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
