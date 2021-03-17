<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Label;

use CultuurNet\UDB3\Search\StringLiteral;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class LabelName extends StringLiteral
{
    public function __construct(string $value)
    {
        $value = trim($value);

        parent::__construct($value);

        if (false !== strpos($value, ';')) {
            throw new UnsupportedParameterValue(
                "Value for argument $value should not contain semicolons."
            );
        }

        $length = mb_strlen($value);
        if ($length < 2) {
            throw new UnsupportedParameterValue(
                "Value for argument $value should not be shorter than 2 chars."
            );
        }

        if ($length > 255) {
            throw new UnsupportedParameterValue(
                "Value for argument $value should not be longer than 255 chars."
            );
        }
    }
}
