<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OfferTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_place_offer_type_from_context(): void
    {
        $this->assertEquals(
            OfferType::fromContext('/contexts/place'),
            OfferType::place()
        );
    }

    /**
     * @test
     */
    public function it_can_create_event_offer_type_from_context(): void
    {
        $this->assertEquals(
            OfferType::fromContext('/contexts/event'),
            OfferType::event()
        );
    }

    /**
     * @test
     */
    public function it_cannot_create_offer_type_from_invalid_context(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->assertEquals(
            OfferType::fromContext('/contexts/NOT_A_VALID_CONTEXT'),
            OfferType::event()
        );
    }
}
