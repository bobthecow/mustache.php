#!/usr/bin/env php
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
 * A shell script to create a single-file class cache of the entire Mustache
 * library.
 *
 *     $ bin/build_bootstrap.php
 *
 * ... will create a `mustache.php` bootstrap file in the project directory,
 * containing all Mustache library classes. This file can then be included in
 * your project, rather than requiring the Mustache Autoloader.
 */
$baseDir = realpath(dirname(__FILE__) . '/..');

require $baseDir . '/src/Mustache/Autoloader.php';
Mustache_Autoloader::register();

// delete the old file
$file = $baseDir . '/mustache.php';
if (file_exists($file)) {
    unlink($file);
}

// and load the new one
SymfonyClassCollectionLoader::load(array(
    'Mustache_Engine',
    'Mustache_Cache',
    'Mustache_Cache_AbstractCache',
    'Mustache_Cache_FilesystemCache',
    'Mustache_Cache_NoopCache',
    'Mustache_Compiler',
    'Mustache_Context',
    'Mustache_Exception',
    'Mustache_Exception_InvalidArgumentException',
    'Mustache_Exception_LogicException',
    'Mustache_Exception_RuntimeException',
    'Mustache_Exception_SyntaxException',
    'Mustache_Exception_UnknownFilterException',
    'Mustache_Exception_UnknownHelperException',
    'Mustache_Exception_UnknownTemplateException',
    'Mustache_HelperCollection',
    'Mustache_LambdaHelper',
    'Mustache_Loader',
    'Mustache_Loader_ArrayLoader',
    'Mustache_Loader_CascadingLoader',
    'Mustache_Loader_FilesystemLoader',
    'Mustache_Loader_InlineLoader',
    'Mustache_Loader_MutableLoader',
    'Mustache_Loader_StringLoader',
    'Mustache_Logger',
    'Mustache_Logger_AbstractLogger',
    'Mustache_Logger_StreamLogger',
    'Mustache_Parser',
    'Mustache_Template',
    'Mustache_Tokenizer',
), dirname($file), basename($file, '.php'));

/**
 * SymfonyClassCollectionLoader.
 *
 * Based heavily on the Symfony ClassCollectionLoader component, with all
 * the unnecessary bits removed.
 *
 * @license http://www.opensource.org/licenses/MIT
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SymfonyClassCollectionLoader
{
    private static $loaded;

    const HEADER = <<<EOS
<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-%d Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
EOS;

    /**
     * Loads a list of classes and caches them in one big file.
     *
     * @param array  $classes   An array of classes to load
     * @param string $cacheDir  A cache directory
     * @param string $name      The cache name prefix
     * @param string $extension File extension of the resulting file
     *
     * @throws InvalidArgumentException When class can't be loaded
     */
    public static function load(array $classes, $cacheDir, $name, $extension = '.php')
    {
        // each $name can only be loaded once per PHP process
        if (isset(self::$loaded[$name])) {
            return;
        }

        self::$loaded[$name] = true;

        $content = '';
        foreach ($classes as $class) {
            if (!class_exists($class) && !interface_exists($class) && (!function_exists('trait_exists') || !trait_exists($class))) {
                throw new InvalidArgumentException(sprintf('Unable to load class "%s"', $class));
            }

            $r = new ReflectionClass($class);
            $content .= preg_replace(array('/^\s*<\?php/', '/\?>\s*$/'), '', file_get_contents($r->getFileName()));
        }

        $cache  = $cacheDir . '/' . $name . $extension;
        $header = sprintf(self::HEADER, strftime('%Y'));
        self::writeCacheFile($cache, $header . substr(self::stripComments('<?php ' . $content), 5));
    }

    /**
     * Writes a cache file.
     *
     * @param string $file    Filename
     * @param string $content Temporary file content
     *
     * @throws RuntimeException when a cache file cannot be written
     */
    private static function writeCacheFile($file, $content)
    {
        $tmpFile = tempnam(dirname($file), basename($file));
        if (false !== @file_put_contents($tmpFile, $content) && @rename($tmpFile, $file)) {
            chmod($file, 0666 & ~umask());

            return;
        }

        throw new RuntimeException(sprintf('Failed to write cache file "%s".', $file));
    }

    /**
     * Removes comments from a PHP source string.
     *
     * We don't use the PHP php_strip_whitespace() function
     * as we want the content to be readable and well-formatted.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the comments removed
     */
    private static function stripComments($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (!in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= $token[1];
            }
        }

        // replace multiple new lines with a single newline
        $output = preg_replace(array('/\s+$/Sm', '/\n+/S'), "\n", $output);

        return $output;
    }
}
