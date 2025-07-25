<?php

$router = $di->getRouter();

$router->add('/', [
    'controller' => 'indihome',
    'action'     => 'index'
]);

$router->addPost('/indihome/number', [
    'controller' => 'indihome',
    'action'     => 'number'
]);



return $router;
