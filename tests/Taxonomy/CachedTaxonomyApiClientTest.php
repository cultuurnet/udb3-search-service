<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Taxonomy;

use CultuurNet\UDB3\Search\Json;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachedTaxonomyApiClientTest extends TestCase
{
    private TaxonomyApiClient&MockObject $baseTaxonomyApiClient;

    private CachedTaxonomyApiClient $cachedTaxonomyApiClient;

    public function setUp(): void
    {
        $this->baseTaxonomyApiClient = $this->createMock(TaxonomyApiClient::class);

        $this->cachedTaxonomyApiClient = new CachedTaxonomyApiClient(
            new ArrayAdapter(),
            $this->baseTaxonomyApiClient
        );
    }

    /**
     * @test
     */
    public function it_delegates_getTypes_to_base_client_on_cache_miss(): void
    {
        $expected = [
            '0.50.4.0.0' => [
                'name' => [
                    'nl' => 'Concert',
                    'fr' => 'Concert',
                    'de' => 'Konzert',
                    'en' => 'Concert',
                ],
            ],
        ];

        $this->baseTaxonomyApiClient->expects($this->once())
            ->method('getTypes')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->cachedTaxonomyApiClient->getTypes());
    }

    /**
     * @test
     */
    public function it_returns_cached_types_on_second_call(): void
    {
        $expected = [
            '0.50.4.0.0' => [
                'name' => [
                    'nl' => 'Concert',
                    'fr' => 'Concert',
                    'de' => 'Konzert',
                    'en' => 'Concert',
                ],
            ],
        ];

        $this->baseTaxonomyApiClient->expects($this->once())
            ->method('getTypes')
            ->willReturn($expected);

        $this->cachedTaxonomyApiClient->getTypes();
        $this->assertEquals($expected, $this->cachedTaxonomyApiClient->getTypes());
    }

    /**
     * @test
     */
    public function it_delegates_getThemes_to_base_client_on_cache_miss(): void
    {
        $expected = [
            '1.8.2.0.0' => [
                'name' => [
                    'nl' => 'Jazz en blues',
                    'fr' => 'Jazz et blues',
                    'de' => 'Jazz und blues',
                    'en' => 'Jazz and blues',
                ],
            ],
        ];

        $this->baseTaxonomyApiClient->expects($this->once())
            ->method('getThemes')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->cachedTaxonomyApiClient->getThemes());
    }

    /**
     * @test
     */
    public function it_returns_cached_themes_on_second_call(): void
    {
        $expected = [
            '1.8.2.0.0' => [
                'name' => [
                    'nl' => 'Jazz en blues',
                    'fr' => 'Jazz et blues',
                    'de' => 'Jazz und blues',
                    'en' => 'Jazz and blues',
                ],
            ],
        ];

        $this->baseTaxonomyApiClient->expects($this->once())
            ->method('getThemes')
            ->willReturn($expected);

        $this->cachedTaxonomyApiClient->getThemes();
        $this->assertEquals($expected, $this->cachedTaxonomyApiClient->getThemes());
    }

    /**
     * @test
     */
    public function it_delegates_getFacilities_to_base_client_on_cache_miss(): void
    {
        $expected = [
            '3.23.1.0.0' => [
                'name' => [
                    'nl' => 'Voorzieningen voor rolstoelgebruikers',
                    'fr' => 'Facilités pour fauteuils roulants',
                    'de' => 'EInrichtung für Rollstuhlfahrer',
                    'en' => 'Wheelchair facilities',
                ],
            ],
        ];

        $this->baseTaxonomyApiClient->expects($this->once())
            ->method('getFacilities')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->cachedTaxonomyApiClient->getFacilities());
    }

    /**
     * @test
     */
    public function it_returns_cached_facilities_on_second_call(): void
    {
        $expected = [
            '3.23.1.0.0' => [
                'name' => [
                    'nl' => 'Voorzieningen voor rolstoelgebruikers',
                    'fr' => 'Facilités pour fauteuils roulants',
                    'de' => 'EInrichtung für Rollstuhlfahrer',
                    'en' => 'Wheelchair facilities',
                ],
            ],
        ];

        $this->baseTaxonomyApiClient->expects($this->once())
            ->method('getFacilities')
            ->willReturn($expected);

        $this->cachedTaxonomyApiClient->getFacilities();
        $this->assertEquals($expected, $this->cachedTaxonomyApiClient->getFacilities());
    }

    /**
     * @test
     */
    public function it_caches_each_domain_independently(): void
    {
        $types = [
            '0.50.4.0.0' => [
                'name' => [
                    'nl' => 'Concert',
                    'fr' => 'Concert',
                    'de' => 'Konzert',
                    'en' => 'Concert',
                ],
            ],
        ];
        $themes = [
            '1.8.2.0.0' => [
                'name' => [
                    'nl' => 'Jazz en blues',
                    'fr' => 'Jazz et blues',
                    'de' => 'Jazz und blues',
                    'en' => 'Jazz and blues',
                ],
            ],
        ];

        $this->baseTaxonomyApiClient->expects($this->once())
            ->method('getTypes')
            ->willReturn($types);

        $this->baseTaxonomyApiClient->expects($this->once())
            ->method('getThemes')
            ->willReturn($themes);

        $this->assertEquals($types, $this->cachedTaxonomyApiClient->getTypes());
        $this->assertEquals($themes, $this->cachedTaxonomyApiClient->getThemes());
    }

    /**
     * @test
     *
     * @bugfix https://jira.publiq.be/browse/III-7156
     * Previously JsonTaxonomyApiClient performed the HTTP fetch in its constructor,
     * so wrapping it in CachedTaxonomyApiClient never short-circuited the call.
     * This test wires both real classes together and asserts no HTTP call happens until a getter is actually invoked.
     */
    public function it_does_not_call_the_taxonomy_api_when_no_getter_is_invoked(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->never())->method('sendRequest');

        new CachedTaxonomyApiClient(
            new ArrayAdapter(),
            new JsonTaxonomyApiClient($httpClient, 'https://taxonomy.example.com/terms', new NullLogger())
        );
    }

    /**
     * @test
     */
    public function it_calls_the_taxonomy_api_only_once_across_many_getter_calls(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(
                new Response(200, [], Json::encode(['terms' => [
                    ['id' => '0.50.4.0.0', 'domain' => 'eventtype', 'name' => ['nl' => 'Concert'], 'scope' => 'events'],
                    ['id' => '1.8.2.0.0', 'domain' => 'theme', 'name' => ['nl' => 'Jazz en blues'], 'scope' => 'events'],
                    ['id' => '3.23.1.0.0', 'domain' => 'facility', 'name' => ['nl' => 'Voorzieningen voor rolstoelgebruikers'], 'scope' => 'events'],
                ]]))
            );

        $client = new CachedTaxonomyApiClient(
            new ArrayAdapter(),
            new JsonTaxonomyApiClient($httpClient, 'https://taxonomy.example.com/terms', new NullLogger())
        );

        $client->getTypes();
        $client->getThemes();
        $client->getFacilities();
    }
}
