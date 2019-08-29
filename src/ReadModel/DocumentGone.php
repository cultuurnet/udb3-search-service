<?php

namespace CultuurNet\UDB3\Search\ReadModel;

use RuntimeException;

class DocumentGone extends RuntimeException
{
    public function __construct($message = '', $code = 410, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
