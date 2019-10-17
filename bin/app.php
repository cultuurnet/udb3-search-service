#!/usr/bin/env php
<?php

use CultuurNet\UDB3\SearchService\Factory\ConfigFactory;
use CultuurNet\UDB3\SearchService\Factory\ContainerFactory;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$container = ContainerFactory::forCli();
$config = ConfigFactory::create(__DIR__ . '/../');
$container = ContainerFactory::forCli($config);
$app = $container->get(Application::class);
$app->run();
