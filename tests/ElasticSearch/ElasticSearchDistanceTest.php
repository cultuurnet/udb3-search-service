<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\MockDistance;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ElasticSearchDistanceTest extends TestCase
{
    /**
     * @test
     * @dataProvider validDistanceProvider
     */
    public function it_accepts_valid_elasticsearch_distances(string $givenDistanceString, string $expectedDistanceString): void
    {
        $distance = new ElasticSearchDistance($givenDistanceString);
        $this->assertEquals($expectedDistanceString, $distance->toString());
    }


    public function validDistanceProvider(): array
    {
        $data = [];

        $values = [0, 30, 1.25, 10.5, 10000.5];

        $units = [
            'mi',
            'miles',
            'yd',
            'yards',
            'ft',
            'feet',
            'in',
            'inch',
            'km',
            'kilometers',
            'm',
            'meters',
            'cm',
            'centimeters',
            'mm',
            'millimeters',
            'NM',
            'nmi',
            'nauticalmiles',
        ];

        foreach ($units as $unit) {
            foreach ($values as $value) {
                $expected = $value . $unit;

                $data[] = [$value . $unit, $expected];
                $data[] = [$value . ' ' . $unit, $expected];
                $data[] = [' ' . $value . ' ' . $unit . ' ', $expected];
                $data[] = [' ' . $value . $unit . ' ', $expected];
            }
        }

        return $data;
    }

    /**
     * @test
     * @dataProvider malformedDistanceProvider
     *
     */
    public function it_throws_an_exception_if_the_distance_string_is_malformed(string $malformedDistanceString): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance is not in a valid format.');
        new ElasticSearchDistance($malformedDistanceString);
    }


    public function malformedDistanceProvider(): array
    {
        return [
            ['about 30km'],
            ['30km range'],
            ['3 beard-seconds'],
            ['3-5 meter'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidUnitProvider
     *
     */
    public function it_throws_an_exception_if_the_distance_string_uses_an_invalid_unit(string $invalidUnitDistanceString): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance uses an unsupported unit.');
        new ElasticSearchDistance($invalidUnitDistanceString);
    }


    public function invalidUnitProvider(): array
    {
        return [
            ['30 kilometer'],
            ['5 meter'],
            ['7 footsteps'],
            ['8 sheppey'],
        ];
    }

    /**
     * @test
     */
    public function it_creates_an_equal_distance_from_an_elasticsearch_distance(): void
    {
        $distance = new ElasticSearchDistance('30km');

        $this->assertEquals($distance, ElasticSearchDistance::fromDistance($distance));
    }

    /**
     * @test
     */
    public function it_converts_another_distance_to_an_elasticsearch_distance(): void
    {
        $distance = ElasticSearchDistance::fromDistance(new MockDistance(' 30 km '));

        $this->assertEquals(new ElasticSearchDistance('30km'), $distance);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_converting_an_invalid_distance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance is not in a valid format.');
        ElasticSearchDistance::fromDistance(new MockDistance('about 30km'));
    }
}
