<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Deserializer;

interface DeserializerLocatorInterface
{
    public function getDeserializerForContentType(string $contentType): DeserializerInterface;
}
