<?php

namespace CultuurNet\UDB3\Search\Deserializer;

use ValueObjects\StringLiteral\StringLiteral;

interface DeserializerInterface
{
    public function deserialize(StringLiteral $data);
}
