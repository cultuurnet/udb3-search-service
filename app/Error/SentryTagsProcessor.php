<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Monolog\Processor\ProcessorInterface;

final class SentryTagsProcessor implements ProcessorInterface
{
    /**
     * @var ApiKey|null
     */
    private $apiKey;

    /**
     * @var bool
     */
    private $console;

    private function __construct(?ApiKey $apiKey, bool $console)
    {
        $this->apiKey = $apiKey;
        $this->console = $console;
    }

    public static function forWeb(?ApiKey $apiKey): SentryTagsProcessor
    {
        return new self($apiKey, false);
    }

    public static function forCli(): SentryTagsProcessor
    {
        return new self(null, true);
    }

    public function __invoke(array $record): array
    {
        $record['context']['tags'] = [
            'api_key' => $this->apiKey ? $this->apiKey->toString() : 'null',
            'runtime.env' => $this->console ? 'cli' : 'web',
        ];
        return $record;
    }
}
