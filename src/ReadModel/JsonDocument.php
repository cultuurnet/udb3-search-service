<?php
declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ReadModel;

use Broadway\ReadModel\ReadModelInterface;
use stdClass;

final class JsonDocument implements ReadModelInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $body;

    public function __construct(string $id, string $rawBody = '{}')
    {
        $this->id = $id;
        $this->body = $rawBody;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function getBody(): stdClass
    {
        return json_decode($this->body);
    }

    public function getRawBody(): string
    {
        return $this->body;
    }

    public function withBody($body): JsonDocument
    {
        return new self($this->id, json_encode($body));
    }

    public function apply(callable $fn): JsonDocument
    {
        $body = $fn($this->getBody());
        return $this->withBody($body);
    }
}
