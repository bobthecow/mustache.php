<?php
namespace Mustache\Loader;

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A Mustache Template cascading loader implementation, which delegates to other
 * Loader instances.
 */
class CascadingLoader implements \Mustache\Loader
{
    private $loaders;

    /**
     * Construct a CascadingLoader with an array of loaders:
     *
     *     $loader = new \Mustache\Loader\CascadingLoader(array(
     *         new \Mustache\Loader\InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__),
     *         new \Mustache\Loader\FilesystemLoader(__DIR__.'/templates')
     *     ));
     *
     * @param \Mustache\Loader[] $loaders
     */
    public function __construct(array $loaders = array())
    {
        $this->loaders = array();
        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }

    /**
     * Add a Loader instance.
     *
     * @param \Mustache\Loader $loader
     */
    public function addLoader(\Mustache\Loader $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * Load a Template by name.
     *
     * @throws \Mustache\Exception\UnknownTemplateException If a template file is not found.
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    public function load($name)
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->load($name);
            } catch (\Mustache\Exception\UnknownTemplateException $e) {
                // do nothing, check the next loader.
            }
        }

        throw new \Mustache\Exception\UnknownTemplateException($name);
    }
}
