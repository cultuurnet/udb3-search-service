<?php

use League\Container\Container;
use League\Container\ReflectionContainer;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new Container();

$container->delegate(new ReflectionContainer());
