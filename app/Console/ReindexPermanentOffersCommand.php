<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\ReindexPermanentOffers;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ReindexPermanentOffersCommand extends AbstractReindexCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('udb3-core:reindex-permanent')
            ->setDescription(
                'Re-indexes existing permanent documents in udb3_core, from the read alias to the write alias.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $operation = new ReindexPermanentOffers(
            $this->getElasticSearchClient(),
            $this->getLogger($output),
            $this->getEventBus(),
            $this->getScrollTtl(),
            $this->getScrollSize()
        );

        $this->runOperation($input, $output, $operation);

        return 0;
    }
}
