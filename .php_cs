<?php

$config = new Symfony\CS\Config\Config();
$config->getFinder()->exclude('bin');

return $config;
