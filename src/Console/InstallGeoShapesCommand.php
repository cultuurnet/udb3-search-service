<?php

namespace CultuurNet\UDB3\SearchService\Console;

use Knp\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class InstallGeoShapesCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('geoshapes:install')
            ->setDescription(
                'Installs the latest geoshapes index, and indexes all geoshape documents.'
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

        $logger->info('Checking which geoshapes indices exist...');

        $previousIndexExists = $consoleApp->find('geoshapes:test-previous')->run($emptyInput, $output) === 0;
        $latestIndexExists = $consoleApp->find('geoshapes:test-latest')->run($emptyInput, $output) === 0;

        if ($latestIndexExists && !$force) {
            // Latest index already exists, do nothing.
            $logger->info('Latest geoshapes index already exists, aborting installation.');
            return;
        } elseif ($latestIndexExists && $force) {
            // Latest index already exists, but force enabled so continue.
            $logger->warning('Latest geoshapes index already exists. Force enabled so continuing.');
        } else {
            // Latest index does not exist, so continue.
            $logger->info('Newer geoshapes index available, starting installation.');
        }

        // Create the latest index.
        $createInput = new ArrayInput(['--force' => $force]);
        $consoleApp->find('geoshapes:create-latest')->run($createInput, $output);

        // Create the region mapping on the latest index.
        $consoleApp->find('geoshapes:region-mapping')->run($emptyInput, $output);

        // Put the write alias on the latest index.
        $consoleApp->find('geoshapes:update-write-alias')->run($emptyInput, $output);

        // (Re)index geoshape documents.
        $consoleApp->find('geoshapes:index')->run($emptyInput, $output);

        // Put the read alias on the latest index.
        $consoleApp->find('geoshapes:update-read-alias')->run($emptyInput, $output);

        // Delete the previous index.
        if ($previousIndexExists) {
            $consoleApp->find('geoshapes:delete-previous')->run($emptyInput, $output);
        }

        $logger->info('Installation completed.');
    }
}
