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
 * Mustache Template filesystem Loader implementation.
 *
 * A FilesystemLoader instance loads Mustache Template source from the filesystem by name:
 *
 *     $loader = new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views');
 *     $tpl = $loader->load('foo'); // equivalent to `file_get_contents(dirname(__FILE__).'/views/foo.mustache');
 *
 * This is probably the most useful Mustache Loader implementation. It can be used for partials and normal Templates:
 *
 *     $m = new Mustache(array(
 *          'loader'          => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views'),
 *          'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
 *     ));
 */
class Mustache_Loader_FilesystemLoader implements Mustache_Loader
{
    private $baseDir;
    private $extension = '.mustache';
    private $templates = array();

    /**
     * Mustache filesystem Loader constructor.
     *
     * Passing an $options array allows overriding certain Loader options during instantiation:
     *
     *     $options = array(
     *         // The filename extension used for Mustache templates. Defaults to '.mustache'
     *         'extension' => '.ms',
     *     );
     *
     * @throws Mustache_Exception_RuntimeException if $baseDir does not exist.
     *
     * @param string $baseDir Base directory containing Mustache template files.
     * @param array  $options Array of Loader options (default: array())
     */
    public function __construct($baseDir, array $options = array())
    {
        if (is_array($baseDir)) {
            $this->baseDir = [];
            foreach ($baseDir as $dir) {
                $this->baseDir[] = $this->sanitizeDir($dir);
            }
        } else {
            $this->baseDir = array($this->sanitizeDir($baseDir));
        }

        if (empty($this->baseDir)) {
            throw new Mustache_Exception_RuntimeException('FilesystemLoader baseDir must have at least one directory.');
        }

        if (array_key_exists('extension', $options)) {
            if (empty($options['extension'])) {
                $this->extension = '';
            } else {
                $this->extension = '.' . ltrim($options['extension'], '.');
            }
        }
    }

    /**
     * Sanitize a directory name.
     *
     * @throws Mustache_Exception_RuntimeException
     *
     * @param string $dir
     *
     * @return string
     */
    protected function sanitizeDir($dir)
    {
        if (strpos($dir, '://') === false) {
            $dir = realpath($dir);
        }

        if (!is_dir($dir)) {
            throw new Mustache_Exception_RuntimeException(sprintf('FilesystemLoader baseDir must be a directory: %s', $dir));
        }
        
        return $dir;
    }

    /**
     * Load a Template by name.
     *
     *     $loader = new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views');
     *     $loader->load('admin/dashboard'); // loads "./views/admin/dashboard.mustache";
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    public function load($name)
    {
        if (!isset($this->templates[$name])) {
            $this->templates[$name] = $this->loadFile($name);
        }

        return $this->templates[$name];
    }

    /**
     * Helper function for loading a Mustache file by name.
     *
     * @throws Mustache_Exception_UnknownTemplateException If a template file is not found.
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    protected function loadFile($name)
    {
        foreach ($this->baseDir as $dir) {
            $fileName = $this->getFileName($name, $dir);

            if (file_exists($fileName)) {
                return file_get_contents($fileName);
            }
        }

        // No template found
        throw new Mustache_Exception_UnknownTemplateException($name);
    }

    /**
     * Helper function for getting a Mustache template file name.
     *
     * @param string $name
     * @param string $dir
     *
     * @return string Template file name
     */
    protected function getFileName($name, $dir = null)
    {
        $fileName = $dir . '/' . $name;
        if (substr($fileName, 0 - strlen($this->extension)) !== $this->extension) {
            $fileName .= $this->extension;
        }

        return $fileName;
    }
}
