<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use CultureFeed_Consumer;
use CultuurNet\UDB3\Search\LoggerAwareTrait;
use Exception;
use ICultureFeed;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class CultureFeedConsumerResolver implements ConsumerResolver
{
    use LoggerAwareTrait;

    private ICultureFeed $cultureFeed;
    private LoggerInterface $logger;

    public function __construct(ICultureFeed $cultureFeed)
    {
        $this->cultureFeed = $cultureFeed;
        $this->setLogger(new NullLogger());
    }

    public function getStatus(string $apiKey): string
    {
        try {
            /** @var CultureFeed_Consumer $cultureFeedConsumer */
            $cultureFeedConsumer = $this->cultureFeed->getServiceConsumerByApiKey($apiKey, true);
        } catch (Exception $exception) {
            return 'INVALID';
        }
        return $cultureFeedConsumer->status;
    }

    public function getDefaultQuery(string $apiKey): ?string
    {
        try {
            /** @var CultureFeed_Consumer $cultureFeedConsumer */
            $cultureFeedConsumer = $this->cultureFeed->getServiceConsumerByApiKey($apiKey, true);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return null;
        }

        return $cultureFeedConsumer->searchPrefixSapi3;
    }
}
