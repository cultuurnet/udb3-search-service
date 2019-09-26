#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;

/** @var \League\Container\Container $container */
$container = require __DIR__ . '/../container.php';
$app = $container->get(Application::class);
$app->run();


