<?php

namespace CultuurNet\UDB3\SearchService\Console;

use Knp\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class InstallUDB3CoreCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('udb3-core:install')
            ->setDescription(
                'Installs the latest udb3_core index, and migrates documents from the previous index if possible.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Deletes the latest index first if it already exists.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = (bool) $input->getOption('force');

        $emptyInput = new ArrayInput([]);
        $consoleApp = $this->getApplication();

        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $logger->info('Checking which udb3_core indices exist...');

        $previousIndexExists = $consoleApp->find('udb3-core:check-previous')->run($emptyInput, $output) === 0;
        $latestIndexExists = $consoleApp->find('udb3-core:check-latest')->run($emptyInput, $output) === 0;

        if ($latestIndexExists && !$force) {
            // Latest index already exists, do nothing.
            $logger->info('Latest udb3_core index already exists, aborting installation.');
            return;
        } elseif ($latestIndexExists && $force) {
            // Latest index already exists, but force enabled so continue.
            $logger->warning('Latest udb3_core index already exists. Force enabled so continuing.');
        } else {
            // Latest index does not exist, so continue.
            $logger->info('Newer udb3_core index available, starting installation.');
        }

        // Create the latest index.
        $createInput = new ArrayInput(['--force' => $force]);
        $consoleApp->find('udb3-core:create-latest')->run($createInput, $output);

        // Create the organizer mapping on the latest index.
        $consoleApp->find('udb3-core:organizer-mapping')->run($emptyInput, $output);

        // Create the event mapping on the latest index.
        $consoleApp->find('udb3-core:event-mapping')->run($emptyInput, $output);

        // Create the place mapping on the latest index.
        $consoleApp->find('udb3-core:place-mapping')->run($emptyInput, $output);

        // Put the write alias on the latest index.
        $consoleApp->find('udb3-core:update-write-alias')->run($emptyInput, $output);

        // Reindex from a previous index to the latest index.
        if ($previousIndexExists) {
            $consoleApp->find('udb3-core:reindex')->run($emptyInput, $output);
        }

        // Put the read alias on the latest index.
        $consoleApp->find('udb3-core:update-read-alias')->run($emptyInput, $output);

        // Delete the previous index.
        if ($previousIndexExists) {
            $consoleApp->find('udb3-core:delete-previous')->run($emptyInput, $output);
        }

        $logger->info('Installation completed.');
    }
}
