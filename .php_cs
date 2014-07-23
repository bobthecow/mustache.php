<?php

return Symfony\CS\Config\Config::create()->finder(Symfony\CS\Finder\DefaultFinder::create()
    ->notName('LICENSE')
    ->notName('README.md')
    ->notName('composer.*')
    ->notName('phpunit.xml.*')
    ->notName('*.phar')
    ->exclude('vendor')
    ->in(__DIR__)
);
