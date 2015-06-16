<?php

$router = new Phalcon\Mvc\Router(false);

$router->notFound(array(
    "namespace" => 'App\Controllers',
    "controller" => "error",
    "action" => "notFound"
));

$router->add('/:controller/:action/:int', array(
    'controller' => 1,
    'action' => 2,
    'id' => 3
))->setName('full');

$router->add('/:controller/:action', array(
    'controller' => 1,
    'action' => 2
))->setName('action');

$router->add('/:controller', array(
    'controller' => 1,
    'action' => 'index'
))->setName('controller');

$router->add('/{action:(login|logout)}', array(
    'namespace' => 'App\Controllers',
    'controller' => 'index'
))->setName('auth');

$router->add('/ajax/:action', array(
    'controller' => 'ajax',
    'action' => 1
))->setName('ajax');

$router->add("/", array(
    'controller' => 'index',
    'action' => 'index'
))->setName('main');