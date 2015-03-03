<?php

use Symfony\CS\Config\Config;
use Symfony\CS\FixerInterface;

$config = Config::create()
    // use symfony level and extra fixers:
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers(array('align_double_arrow', '-concat_without_spaces', 'concat_with_spaces', 'ordered_use', 'strict'))
    ->setUsingLinter(false);

$finder = $config->getFinder()
    ->in(__DIR__);

// exclude file due to error on PHP 5.3 that ignore content after __halt_compiler when using token_get_all
if (version_compare(PHP_VERSION, '5.4', '<')) {
    $finder->notPath('test/Mustache/Test/Loader/InlineLoaderTest.php');
}

return $config;
