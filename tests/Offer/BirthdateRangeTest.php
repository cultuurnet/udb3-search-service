<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BirthdateRangeTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_from_and_to_dates(): void
    {
        $from = new DateTimeImmutable('2020-01-01');
        $to = new DateTimeImmutable('2020-12-31');

        $range = new BirthdateRange($from, $to);

        $this->assertSame($from, $range->getFrom());
        $this->assertSame($to, $range->getTo());
    }

    /**
     * @test
     */
    public function it_throws_when_from_is_after_to(): void
    {
        $from = new DateTimeImmutable('2020-12-31');
        $to = new DateTimeImmutable('2020-01-01');

        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage(
            'Start birthdate date should be equal to or smaller than end birthdate date.'
        );

        new BirthdateRange($from, $to);
    }
}
