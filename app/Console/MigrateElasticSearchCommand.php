<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class MigrateElasticSearchCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('elasticsearch:migrate')
            ->setDescription(
                'Installs or migrates all indices.'
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
        $inputWithForceOption = new ArrayInput(['--force' => $force]);

        $consoleApp = $this->getApplication();

        $consoleApp->find('lowercase-exact-match-analyzer:create')->run($emptyInput, $output);
        $consoleApp->find('lowercase-standard-analyzer:create')->run($emptyInput, $output);
        $consoleApp->find('autocomplete-analyzer:create')->run($emptyInput, $output);

        $consoleApp->find('geoshapes:install')->run($inputWithForceOption, $output);
        $consoleApp->find('udb3-core:install')->run($inputWithForceOption, $output);

        return 0;
    }
}
