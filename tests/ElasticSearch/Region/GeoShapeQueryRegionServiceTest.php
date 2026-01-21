<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Region;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchResponseHelper;
use CultuurNet\UDB3\Search\ElasticSearch\MocksElasticsearchResponse;
use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\Region\RegionId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GeoShapeQueryRegionServiceTest extends TestCase
{
    use MocksElasticsearchResponse;
    use ElasticSearchResponseHelper;

    private ElasticSearchClientInterface&MockObject $client;

    private GeoShapeQueryRegionService $regionService;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ElasticSearchClientInterface::class);

        $this->regionService = new GeoShapeQueryRegionService(
            $this->client,
            'mock'
        );
    }

    /**
     * @test
     */
    public function it_uses_a_percolate_query_and_returns_all_region_ids_of_the_matching_queries(): void
    {
        $this->client->expects($this->exactly(2))
            ->method('search')
            ->willReturnOnConsecutiveCalls(
                $this->getElasticSearchResponseFromString(FileReader::read(__DIR__ . '/data/regions_1.json')),
                $this->getElasticSearchResponseFromString(FileReader::read(__DIR__ . '/data/regions_2.json')),
            );

        $expectedRegionIds = [
            new RegionId('gem-nieuwerkerken'),
            new RegionId('gem-oostkamp'),
            new RegionId('gem-oostrozebeke'),
            new RegionId('gem-opglabbeek'),
            new RegionId('gem-peer'),
            new RegionId('gem-pittem'),
            new RegionId('gem-putte'),
            new RegionId('gem-ronse'),
            new RegionId('gem-roosdaal'),
            new RegionId('gem-ruiselede'),
            new RegionId('gem-rumst'),
            new RegionId('gem-sint-amands'),
            new RegionId('gem-sint-genesius-rode'),
            new RegionId('gem-sint-laureins'),
            new RegionId('gem-ternat'),
            new RegionId('gem-tervuren'),
            new RegionId('gem-kalmthout'),
            new RegionId('gem-kinrooi'),
            new RegionId('gem-kluisbergen'),
            new RegionId('gem-kortenaken'),
        ];

        $actualRegionIds = $this->regionService->getRegionIds(
            [
                'type' => 'Point',
                'coordinates' => [80.9, -4.5],
            ]
        );

        $this->assertEquals($expectedRegionIds, $actualRegionIds);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_it_gets_an_invalid_response_from_elasticsearch(): void
    {
        $this->client->expects($this->once())
            ->method('search')
            ->willReturn($this->createElasticsearchResponse(Json::decodeAssociatively(FileReader::read(__DIR__ . '/data/regions_invalid.json'))));

        $this->expectException(RuntimeException::class);

        $this->regionService->getRegionIds(
            [
                'type' => 'Point',
                'coordinates' => [80.9, -4.5],
            ]
        );
    }
}
