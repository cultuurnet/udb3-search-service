#!/usr/bin/env php
<?php

use CultuurNet\UDB3\SearchService\Factory\ContainerFactory;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$container = ContainerFactory::forCli();
$app = $container->get(Application::class);
$app->run();
