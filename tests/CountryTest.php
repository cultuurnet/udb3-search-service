<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CountryTest extends TestCase
{
    /**
     * @test
     */
    public function it_only_allows_valid_countries(): void
    {
        new Country('BE');

        $this->expectException(InvalidArgumentException::class);
        new Country('AA');
    }

    public function it_can_be_converted_to_a_string(): void
    {
        $country = new Country('FR');
        $this->assertEquals('FR', $country->toString());
    }
}
