<?php

use Symfony\CS\Config\Config;
use Symfony\CS\Fixer;
use Symfony\CS\FixerInterface;

$fixer = new Fixer();
$fixer->registerBuiltInFixers();

$fixers = array();

foreach ($fixer->getFixers() as $fixer) {
    $level = $fixer->getLevel();

    if (!isset($fixers[$level])) {
        $fixers[$level] = array();
    }

    $fixers[$level][] = $fixer->getName();
}

$fixers = array_merge(
    $fixers[FixerInterface::PSR0_LEVEL],
    $fixers[FixerInterface::PSR1_LEVEL],
    $fixers[FixerInterface::PSR2_LEVEL],
    $fixers[FixerInterface::ALL_LEVEL],
    array('concat_with_spaces', 'ordered_use', 'strict')
);

$config = new Config();
$config->fixers($fixers);
$config->getFinder()->in(__DIR__)->exclude('bin');

return $config;
