<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use PHPUnit\Framework\TestCase;

class ElasticSearchDistanceTest extends TestCase
{
    /**
     * @test
     * @dataProvider validDistanceProvider
     *
     * @param string $givenDistanceString
     * @param string $expectedDistanceString
     */
    public function it_accepts_valid_elasticsearch_distances($givenDistanceString, $expectedDistanceString)
    {
        $distance = new ElasticSearchDistance($givenDistanceString);
        $this->assertEquals($expectedDistanceString, $distance->toNative());
    }

    /**
     * @return array
     */
    public function validDistanceProvider()
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
     * @param string $malformedDistanceString
     */
    public function it_throws_an_exception_if_the_distance_string_is_malformed($malformedDistanceString)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance is not in a valid format.');
        new ElasticSearchDistance($malformedDistanceString);
    }

    /**
     * @return array
     */
    public function malformedDistanceProvider()
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
     * @param string $invalidUnitDistanceString
     */
    public function it_throws_an_exception_if_the_distance_string_uses_an_invalid_unit($invalidUnitDistanceString)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance uses an unsupported unit.');
        new ElasticSearchDistance($invalidUnitDistanceString);
    }

    /**
     * @return array
     */
    public function invalidUnitProvider()
    {
        return [
            ['30 kilometer'],
            ['5 meter'],
            ['7 footsteps'],
            ['8 sheppey'],
        ];
    }
}
