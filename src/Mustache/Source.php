<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2015 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Mustache template Source interface.
 */
interface Mustache_Source
{
    /**
     * Get the Source key (used to generate the compiled class name).
     *
     * @throws RuntimeException when a source file cannot be read
     *
     * @return string
     */
    public function getKey();

    /**
     * Get the template Source.
     *
     * @return string
     */
    public function getSource();
}
