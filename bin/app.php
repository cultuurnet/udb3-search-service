#!/usr/bin/env php
<?php

use CultuurNet\UDB3\SearchService\CommandServiceProvider;
use CultuurNet\UDB3\SearchService\ContainerFactory;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$container = ContainerFactory::build();
$container->addServiceProvider(CommandServiceProvider::class);
$app = $container->get(Application::class);
$app->run();
