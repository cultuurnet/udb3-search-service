#!/usr/bin/env php
<?php

use CultuurNet\UDB3\SearchService\Factory\ConfigFactory;
use CultuurNet\UDB3\SearchService\Factory\ContainerFactory;
use CultuurNet\UDB3\SearchService\Factory\ErrorHandlerFactory;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$config = ConfigFactory::create(__DIR__ . '/../');

$errorHandler = ErrorHandlerFactory::forCli();
$errorHandler->register();

$container = ContainerFactory::forCli($config);
$app = $container->get(Application::class);
$app->run();
