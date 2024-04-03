<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\GetIndexNamesFromAlias;
use Elasticsearch\Client;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class InstallGeoShapesCommand extends AbstractElasticSearchCommand
{
    /**
     * @var string
     */
    private $latestIndexName;

    /**
     * @var string
     */
    private $writeAlias;

    /**
     * @var string
     */
    private $readAlias;

    /**
     * @param string $latestIndexName
     * @param string $writeAlias
     * @param string $readAlias
     */
    public function __construct(
        Client $client,
        $latestIndexName,
        $writeAlias,
        $readAlias
    ) {
        parent::__construct($client);
        $this->latestIndexName = $latestIndexName;
        $this->writeAlias = $writeAlias;
        $this->readAlias = $readAlias;
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
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
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $force = (bool) $input->getOption('force');

        $emptyInput = new ArrayInput([]);
        $consoleApp = $this->getApplication();

        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $logger->info('Checking if latest geoshapes index exists...');

        $inputWithLatestIndexTarget = new ArrayInput(['target' => $this->latestIndexName]);
        $latestIndexExists = $consoleApp->find('index:exists')->run($inputWithLatestIndexTarget, $output) === 0;

        if ($latestIndexExists && !$force) {
            // Latest index already exists, do nothing.
            $logger->info('Latest geoshapes index exists already. Aborting installation.');
            return 0;
        }

        if ($latestIndexExists && $force) {
            // Latest index already exists, but force enabled so continue.
            $logger->warning('Latest geoshapes index exists Already. Force enabled so continuing installation.');
        } else {
            // Latest index does not exist, so continue.
            $logger->info('Latest geoshapes index does not exist yet. Continuing installation.');
        }

        // Create the latest index.
        $createInput = new ArrayInput(['--force' => $force, 'target' => $this->latestIndexName]);
        $consoleApp->find('index:create')->run($createInput, $output);

        // Create the region mapping on the latest index.
        $consoleApp->find('geoshapes:region-mapping')->run($emptyInput, $output);

        // Move the write alias to the newly created index.
        $writeAliasInput = new ArrayInput(['alias' => $this->writeAlias, 'target' => $this->latestIndexName]);
        $consoleApp->find('index:update-alias')->run($writeAliasInput, $output);

        // (Re)index region documents.
        $consoleApp->find('geoshapes:index-regions')->run($emptyInput, $output);

        // Get all index names associated with the read alias.
        $getIndexNames = new GetIndexNamesFromAlias($this->getElasticSearchClient(), new NullLogger());
        $previousIndexNames = $getIndexNames->run($this->readAlias);

        // Delete the previous indices.
        foreach ($previousIndexNames as $previousIndexName) {
            $deleteInput = new ArrayInput(['target' => $previousIndexName]);
            $consoleApp->find('index:delete')->run($deleteInput, $output);
        }

        // Move the read alias to the newly created index.
        $readAliasInput = new ArrayInput(['alias' => $this->readAlias, 'target' => $this->latestIndexName]);
        $consoleApp->find('index:update-alias')->run($readAliasInput, $output);

        $logger->info('Installation completed.');

        return 0;
    }
}
