<?php

namespace CultuurNet\UDB3\SearchService\Console;

use Knp\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateUDB3CoreCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('udb3-core:migrate')
            ->setDescription('Migrates the udb3_core index from the previous index to the latest one.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $emptyInput = new ArrayInput([]);
        $consoleApp = $this->getApplication();

        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $logger->info('Checking if latest index already exists.');

        if ($consoleApp->find('udb3-core:test-latest')->run($emptyInput, $output) === 0) {
            // Latest index already exists, do nothing.
            $logger->info('Latest index already exists, aborting migration.');
            return;
        }

        $logger->info('Newer udb3_core index available, starting migration.');

        // Create the latest index.
        $consoleApp->find('udb3-core:create-latest')->run($emptyInput, $output);

        // Create the organizer mapping on the latest index.
        $consoleApp->find('udb3-core:organizer-mapping')->run($emptyInput, $output);

        // Create the event mapping on the latest index.
        $consoleApp->find('udb3-core:event-mapping')->run($emptyInput, $output);

        // Create the place mapping on the latest index.
        $consoleApp->find('udb3-core:place-mapping')->run($emptyInput, $output);

        // Put the write alias on the latest index.
        $consoleApp->find('udb3-core:update-write-alias')->run($emptyInput, $output);

        // Reindex from a previous index to the latest index.
        $consoleApp->find('udb3-core:reindex')->run($emptyInput, $output);

        // Put the read alias on the latest index.
        $consoleApp->find('udb3-core:update-read-alias')->run($emptyInput, $output);

        // Delete the previous index.
        $consoleApp->find('udb3-core:delete-previous')->run($emptyInput, $output);

        $logger->info('Migration completed.');
    }
}
