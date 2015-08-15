<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2015 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require dirname(__FILE__) . '/../src/Mustache/Autoloader.php';
Mustache_Autoloader::register();
Mustache_Autoloader::register(dirname(__FILE__) . '/../test');

require dirname(__FILE__) . '/../vendor/yaml/lib/sfYamlParser.php';
