<?php

use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Route\Router;
use Slim\Psr7\Factory\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new Container();

$container->delegate(new ReflectionContainer());

$router = new Router();

$request = ServerRequestFactory::createFromGlobals();

$response = $router->dispatch($request);

$emitter = new SapiStreamEmitter();
$emitter->emit($response);
