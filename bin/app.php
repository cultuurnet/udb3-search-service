#!/usr/bin/env php
<?php

use CultuurNet\UDB3\SearchService\Factory\ConfigFactory;
use CultuurNet\UDB3\SearchService\Factory\ContainerFactory;
use CultuurNet\UDB3\SearchService\Factory\ErrorHandlerFactory;
use Sentry\State\HubInterface;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$config = ConfigFactory::create(__DIR__ . '/../');
$container = ContainerFactory::forCli($config);

$errorHandler = ErrorHandlerFactory::forCli($container->get(HubInterface::class));
$errorHandler->register();

$app = $container->get(Application::class);
$app->run();
