<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use Monolog\Processor\ProcessorInterface;

final class SentryTagsProcessor implements ProcessorInterface
{
    /**
     * @var ApiKey
     */
    private $apiKey;

    /**
     * @var bool
     */
    private $console;

    private function __construct(ApiKey $apiKey, bool $console)
    {
        $this->apiKey = $apiKey;
        $this->console = $console;
    }

    public static function forWeb(?ApiKey $apiKey): SentryTagsProcessor
    {
        $apiKey = $apiKey ?? new ApiKey('null');
        return new self($apiKey, false);
    }

    public static function forCli(): SentryTagsProcessor
    {
        return new self(new ApiKey('null'), true);
    }

    public function __invoke(array $record): array
    {
        $record['context']['tags'] = [
            'api_key' => $this->apiKey->toString(),
            'runtime.env' => $this->console ? 'cli' : 'web',
        ];
        return $record;
    }
}
