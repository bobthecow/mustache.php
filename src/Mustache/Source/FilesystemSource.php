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
 * Mustache template Filesystem Source.
 *
 * This template Source uses stat() to generate the Source key, so that using
 * pre-compiled templates doesn't require hitting the disk to read the source.
 * It is more suitable for production use, and is used by default in the
 * ProductionFilesystemLoader.
 */
class Mustache_Source_FilesystemSource implements Mustache_Source
{
    private $stat;
    private $filename;

    /**
     * Filesystem Source constructor.
     *
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Get the Source key (used to generate the compiled class name).
     *
     * @throws RuntimeException when a source file cannot be read
     *
     * @return string
     */
    public function getKey()
    {
        if (!isset($this->stat)) {
            $this->stat = stat($this->filename);
        }

        if ($this->stat === false) {
            throw new RuntimeException(sprintf('Failed to read source file "%s".', $this->filename));
        }

        return sprintf('filename:%s,size:%s,mtime:%s', $this->filename, $this->stat['size'], $this->stat['mtime']);
    }

    /**
     * Get the template Source.
     *
     * @return string
     */
    public function getSource()
    {
        return file_get_contents($this->filename);
    }
}
