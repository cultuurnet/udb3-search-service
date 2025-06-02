<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Deserializer;

interface DeserializerInterface
{
    // @phpstan-ignore-next-line
    public function deserialize(string $data);
}
