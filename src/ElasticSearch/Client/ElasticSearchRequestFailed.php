<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Client;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use RuntimeException;

final class ElasticSearchRequestFailed extends RuntimeException
{
    private array $error;

    private function __construct(string $message, int $status, array $error, ClientResponseException|ServerResponseException $previous)
    {
        parent::__construct($message, $status, $previous);
        $this->error = $error;
    }

    public static function fromResponseException(ClientResponseException|ServerResponseException $exception): self
    {
        // Not Json::decodeAssociatively(): a body that isn't JSON (e.g. a proxy error page) must not
        // replace the original failure with a JSON exception.
        $error = json_decode((string) $exception->getResponse()->getBody(), true);

        return new self(
            $exception->getMessage(),
            $exception->getCode(),
            is_array($error) ? $error : [],
            $exception
        );
    }

    public function isNotFound(): bool
    {
        return $this->getCode() === 404;
    }

    public function getReason(): ?string
    {
        return $this->error['error']['root_cause'][0]['reason'] ?? null;
    }
}
