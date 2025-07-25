<?php

$router = $di->getRouter();

$router->add('/', [
    'controller' => 'indihome',
    'action'     => 'index'
]);

$router->addGet('/indihome', [
    'controller' => 'indihome',
    'action'     => 'getNumber'
]);

$router->addPost('/indihome/number', [
    'controller' => 'indihome',
    'action'     => 'number'
]);



return $router;
