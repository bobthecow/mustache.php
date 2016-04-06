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
 * Mustache Template production filesystem Loader implementation.
 *
 * A production-ready FilesystemLoader, which doesn't require reading a file if it already exists in the template cache.
 *
 * {@inheritdoc}
 */
class Mustache_Loader_ProductionFilesystemLoader extends Mustache_Loader_FilesystemLoader
{
    /**
     * Helper function for loading a Mustache file by name.
     *
     * @throws Mustache_Exception_UnknownTemplateException If a template file is not found.
     *
     * @param string $name
     *
     * @return Mustache_Source Mustache Template source
     */
    protected function loadFile($name)
    {
        $fileName = $this->getFileName($name);

        if (!file_exists($fileName)) {
            throw new Mustache_Exception_UnknownTemplateException($name);
        }

        return new Mustache_Source_FilesystemSource($fileName);
    }
}
