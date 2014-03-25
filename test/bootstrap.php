<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$loader = require dirname(__FILE__).'/../vendor/autoload.php';
$loader->add('Mustache_Test', dirname(__FILE__));

require dirname(__FILE__).'/../vendor/yaml/lib/sfYamlParser.php';
