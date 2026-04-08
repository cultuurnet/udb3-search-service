<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Taxonomy;

use CultuurNet\UDB3\Search\Json;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

final class JsonTaxonomyApiClientTest extends TestCase
{
    private ClientInterface&MockObject $httpClient;

    private LoggerInterface&MockObject $logger;

    public function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * @test
     */
    public function it_filters_terms_by_eventtype_domain(): void
    {
        $taxonomyApiClient = $this->createTaxonomyClientWithTerms($this->sampleTerms());

        $this->assertEquals(
            [
                '0.50.4.0.0' => [
                    'name' => [
                        'nl' => 'Concert',
                        'fr' => 'Concert',
                        'de' => 'Konzert',
                        'en' => 'Concert',
                    ],
                ],
                '0.50.6.0.0' => [
                    'name' => [
                        'nl' => 'Film',
                        'fr' => 'Cinéma',
                        'de' => 'Film',
                        'en' => 'Film',
                    ],
                ],
                'GnPFp9uvOUyqhOckIFMKmg' => [
                    'name' => [
                        'nl' => 'Museum of galerij',
                        'fr' => 'Musée ou galerie',
                        'de' => 'Museum oder Galerie',
                        'en' => 'Museum or gallery',
                    ],
                ],
            ],
            $taxonomyApiClient->getTypes()
        );
    }

    /**
     * @test
     */
    public function it_filters_terms_by_theme_domain(): void
    {
        $taxonomyApiClient = $this->createTaxonomyClientWithTerms($this->sampleTerms());

        $this->assertEquals(
            [
                '1.8.2.0.0' => [
                    'name' => [
                        'nl' => 'Jazz en blues',
                        'fr' => 'Jazz et blues',
                        'de' => 'Jazz und blues',
                        'en' => 'Jazz and blues',
                    ],
                ],
            ],
            $taxonomyApiClient->getThemes()
        );
    }

    /**
     * @test
     */
    public function it_filters_terms_by_facility_domain(): void
    {
        $taxonomyApiClient = $this->createTaxonomyClientWithTerms($this->sampleTerms());

        $this->assertEquals(
            [
                '3.23.1.0.0' => [
                    'name' => [
                        'nl' => 'Voorzieningen voor rolstoelgebruikers',
                        'fr' => 'Facilités pour fauteuils roulants',
                        'de' => 'EInrichtung für Rollstuhlfahrer',
                        'en' => 'Wheelchair facilities',
                    ],
                ],
            ],
            $taxonomyApiClient->getFacilities()
        );
    }

    /**
     * @test
     */
    public function it_throws_when_no_terms_match_domain(): void
    {
        $terms = [
            [
                'id' => '0.50.4.0.0',
                'domain' => 'eventtype',
                'name' => [
                    'nl' => 'Concert',
                    'fr' => 'Concert',
                    'de' => 'Konzert',
                    'en' => 'Concert',
                ],
                'scope' => 'events',
                'otherSuggestedTerms' => [],
            ],
        ];

        $taxonomyApiClient = $this->createTaxonomyClientWithTerms($terms);

        $this->expectException(TaxonomyApiProblem::class);
        $this->expectExceptionMessage('Could not find terms for Domain theme.');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Could not find terms for Domain theme');

        $taxonomyApiClient->getThemes();
    }

    /**
     * @test
     */
    public function it_throws_on_non_200_status_code(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(500, [], 'Internal Server Error'));

        $this->expectException(TaxonomyApiProblem::class);
        $this->expectExceptionMessage('Taxonomy Api returned a non-200 status code.');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Taxonomy Api returned a non-200 status code');

        new JsonTaxonomyApiClient(
            $this->httpClient,
            'https://taxonomy.example.com/terms',
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_throws_on_empty_response_body(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], ''));

        $this->expectException(TaxonomyApiProblem::class);
        $this->expectExceptionMessage('Taxonomy Api returned no terms.');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Taxonomy Api returned no terms');


        new JsonTaxonomyApiClient(
            $this->httpClient,
            'https://taxonomy.example.com/terms',
            $this->logger
        );
    }

    private function createTaxonomyClientWithTerms(array $terms): JsonTaxonomyApiClient
    {
        $body = Json::encode(['terms' => $terms]);

        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $body));

        return new JsonTaxonomyApiClient(
            $this->httpClient,
            'https://taxonomy.example.com/terms',
            $this->logger
        );
    }

    private function sampleTerms(): array
    {
        return [
            [
                'id' => '0.50.4.0.0',
                'domain' => 'eventtype',
                'name' => [
                    'nl' => 'Concert',
                    'fr' => 'Concert',
                    'de' => 'Konzert',
                    'en' => 'Concert',
                ],
                'scope' => 'events',
                'otherSuggestedTerms' => [],
            ],
            [
                'id' => '0.50.6.0.0',
                'domain' => 'eventtype',
                'name' => [
                    'nl' => 'Film',
                    'fr' => 'Cinéma',
                    'de' => 'Film',
                    'en' => 'Film',
                ],
                'scope' => 'events',
                'otherSuggestedTerms' => [],
            ],
            [
                'id' => 'GnPFp9uvOUyqhOckIFMKmg',
                'domain' => 'eventtype',
                'name' => [
                    'nl' => 'Museum of galerij',
                    'fr' => 'Musée ou galerie',
                    'de' => 'Museum oder Galerie',
                    'en' => 'Museum or gallery',
                ],
                'scope' => 'places',
            ],
            [
                'id' => '1.8.2.0.0',
                'domain' => 'theme',
                'name' => [
                    'nl' => 'Jazz en blues',
                    'fr' => 'Jazz et blues',
                    'de' => 'Jazz und blues',
                    'en' => 'Jazz and blues',
                ],
                'scope' => 'events',
                'otherSuggestedTerms' => [],
            ],
            [
                'id' => '3.23.1.0.0',
                'domain' => 'facility',
                'name' => [
                    'nl' => 'Voorzieningen voor rolstoelgebruikers',
                    'fr' => 'Facilités pour fauteuils roulants',
                    'de' => 'EInrichtung für Rollstuhlfahrer',
                    'en' => 'Wheelchair facilities',
                ],
                'scope' => 'events',
                'otherSuggestedTerms' => [],
            ],
        ];
    }
}
