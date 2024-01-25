#!/usr/bin/env php
<?php

use CultuurNet\UDB3\SearchService\Error\LoggerFactory;
use CultuurNet\UDB3\SearchService\Error\LoggerName;
use CultuurNet\UDB3\SearchService\Factory\ConfigFactory;
use CultuurNet\UDB3\SearchService\Factory\ContainerFactory;
use CultuurNet\UDB3\SearchService\Factory\ErrorHandlerFactory;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$config = ConfigFactory::create(__DIR__ . '/../');
$container = ContainerFactory::forCli($config);

$errorLogger = LoggerFactory::create($container, LoggerName::forCli());
$errorHandler = ErrorHandlerFactory::forCli($errorLogger);
$errorHandler->register();

$app = $container->get(Application::class);
$app->setCatchExceptions(false);

$app->run();
