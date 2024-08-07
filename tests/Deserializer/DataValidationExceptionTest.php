<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Deserializer;

use PHPUnit\Framework\TestCase;

final class DataValidationExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function itHasAListOfValidationMessages(): void
    {
        $messages = [
            'Field foo should not be empty.',
            'Field bar should be an integer, string given.',
        ];

        $exception = new DataValidationException();
        $exception->setValidationMessages($messages);

        $this->assertEquals($messages, $exception->getValidationMessages());
    }

    /**
     * @test
     */
    public function itHasAnEmptyListOfValidationMessagesByDefault(): void
    {
        $exception = new DataValidationException();
        $this->assertEquals([], $exception->getValidationMessages());
    }
}
