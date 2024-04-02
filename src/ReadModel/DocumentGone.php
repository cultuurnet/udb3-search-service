<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ReadModel;

use Exception;
use RuntimeException;

final class DocumentGone extends RuntimeException
{
    public function __construct($message = '', $code = 410, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
