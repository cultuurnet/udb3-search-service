<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ReadModel;

use Broadway\ReadModel\Identifiable;
use CultuurNet\UDB3\Search\Json;
use stdClass;

final class JsonDocument implements Identifiable
{
    private string $id;

    private string $body;

    public function __construct(string $id, string $rawBody = '{}')
    {
        $this->id = $id;
        $this->body = $rawBody;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getBody(): stdClass
    {
        return Json::decode($this->body);
    }

    public function getRawBody(): string
    {
        return $this->body;
    }

    public function withBody($body): JsonDocument
    {
        return new self($this->id, Json::encode($body));
    }

    public function apply(callable $fn): JsonDocument
    {
        $body = $fn($this->getBody());
        return $this->withBody($body);
    }
}
