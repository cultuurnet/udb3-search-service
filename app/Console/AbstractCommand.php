<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    public function getApplication(): Application
    {
        $application = parent::getApplication();

        if ($application === null) {
            throw new \RuntimeException('The command is not attached to an application.');
        }

        return $application;
    }
}
