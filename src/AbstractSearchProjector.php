<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentIndexServiceInterface;

abstract class AbstractSearchProjector implements EventListener
{
    private JsonDocumentIndexServiceInterface $indexService;


    public function __construct(
        JsonDocumentIndexServiceInterface $indexService
    ) {
        $this->indexService = $indexService;
    }


    public function handle(DomainMessage $domainMessage): void
    {
        $handlers = $this->getEventHandlers();

        $payload = $domainMessage->getPayload();
        $payloadType = (string) get_class($payload);

        if (array_key_exists($payloadType, $handlers) &&
            method_exists($this, $handlers[$payloadType])) {
            $handler = $handlers[$payloadType];
            $this->{$handler}($payload);
        }
    }

    /**
     * @return array
     */
    abstract protected function getEventHandlers();


    protected function getIndexService(): JsonDocumentIndexServiceInterface
    {
        return $this->indexService;
    }
}
