<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Deserializer;

use ValueObjects\StringLiteral\StringLiteral;

interface DeserializerLocatorInterface
{
    /**
     * @return DeserializerInterface
     */
    public function getDeserializerForContentType(StringLiteral $contentType);
}
