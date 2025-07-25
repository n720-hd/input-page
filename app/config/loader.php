<?php

$loader = new \Phalcon\Autoload\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->setDirectories(
    [
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->servicesDir,
        $config->application->viewsDir,
        $config->application->validatorsDir,
        $config->application->usecasesDir
    ]
)->register();
