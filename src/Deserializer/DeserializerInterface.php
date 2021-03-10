<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Deserializer;

interface DeserializerInterface
{
    public function deserialize(string $data);
}
