<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Deserializer;

use Exception;

final class DataValidationException extends Exception
{
    /**
     * @var string[]
     */
    private array $validationMessages = [];

    /**
     * @param string[] $validationMessages
     */
    public function setValidationMessages(array $validationMessages): void
    {
        $this->validationMessages = $validationMessages;
    }

    /**
     * @return string[]
     */
    public function getValidationMessages()
    {
        return $this->validationMessages;
    }
}
