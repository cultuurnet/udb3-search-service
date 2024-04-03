<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

final class CompositeJsonTransformer implements JsonTransformer
{
    /**
     * @var JsonTransformer[]
     */
    private $jsonTransformers;

    public function __construct(JsonTransformer ...$jsonTransformers)
    {
        $this->jsonTransformers = $jsonTransformers;
    }

    public function addTransformer(JsonTransformer $jsonTransformer): self
    {
        $clone = clone $this;
        $clone->jsonTransformers[] = $jsonTransformer;

        return $clone;
    }

    public function transform(array $from, array $draft = []): array
    {
        return array_reduce(
            $this->jsonTransformers,
            function ($draft, $jsonTransformer) use ($from): array {
                return $jsonTransformer->transform($from, $draft);
            },
            $draft
        );
    }
}
