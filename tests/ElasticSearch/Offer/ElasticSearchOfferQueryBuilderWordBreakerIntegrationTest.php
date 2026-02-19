<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryString;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Start;
use PHPUnit\Framework\TestCase;

final class ElasticSearchOfferQueryBuilderWordBreakerIntegrationTest extends TestCase
{
    /**
     * @test
     */
    public function it_uses_decompounder_fields_when_word_breaker_enabled(): void
    {
        $queryBuilder = new ElasticSearchOfferQueryBuilder(
            aggregationSize: 10,
            useWordBreaker: true
        );

        $query = $queryBuilder
            ->withStartAndLimit(new Start(0), new Limit(10))
            ->withAdvancedQuery(
                new LuceneQueryString('park'),
                new Language('nl')
            )
            ->build();

        // Get the query_string fields
        $queryString = $query['query']['bool']['must'][1]['query_string'] ?? null;
        $this->assertNotNull($queryString, 'query_string should exist in the query');

        $fields = $queryString['fields'] ?? [];

        // Should contain decompounder fields for Dutch
        $this->assertContains('name.nl.decompounder', $fields);
        $this->assertContains('description.nl.decompounder', $fields);
        $this->assertContains('labels_free_text.decompounder', $fields);

        // Should NOT contain standard text fields
        $this->assertNotContains('name.nl', $fields);
        $this->assertNotContains('description.nl', $fields);
        $this->assertNotContains('labels_free_text', $fields);

        // ID fields should still be present without decompounder
        $this->assertContains('id', $fields);
        $this->assertContains('location.id', $fields);
        $this->assertContains('organizer.id', $fields);
    }

    /**
     * @test
     */
    public function it_uses_standard_fields_when_word_breaker_disabled(): void
    {
        $queryBuilder = new ElasticSearchOfferQueryBuilder(
            aggregationSize: 10,
            useWordBreaker: false
        );

        $query = $queryBuilder
            ->withStartAndLimit(new Start(0), new Limit(10))
            ->withAdvancedQuery(
                new LuceneQueryString('park'),
                new Language('nl')
            )
            ->build();

        // Get the query_string fields
        $queryString = $query['query']['bool']['must'][1]['query_string'] ?? null;
        $this->assertNotNull($queryString, 'query_string should exist in the query');

        $fields = $queryString['fields'] ?? [];

        // Should contain standard fields for Dutch
        $this->assertContains('name.nl', $fields);
        $this->assertContains('description.nl', $fields);
        $this->assertContains('labels_free_text', $fields);

        // Should NOT contain decompounder fields
        $this->assertNotContains('name.nl.decompounder', $fields);
        $this->assertNotContains('description.nl.decompounder', $fields);
        $this->assertNotContains('labels_free_text.decompounder', $fields);

        // ID fields should be present
        $this->assertContains('id', $fields);
        $this->assertContains('location.id', $fields);
        $this->assertContains('organizer.id', $fields);
    }

    /**
     * @test
     */
    public function it_defaults_to_word_breaker_disabled(): void
    {
        // Constructor without useWordBreaker parameter
        $queryBuilder = new ElasticSearchOfferQueryBuilder(aggregationSize: 10);

        $query = $queryBuilder
            ->withStartAndLimit(new Start(0), new Limit(10))
            ->withAdvancedQuery(
                new LuceneQueryString('test'),
                new Language('nl')
            )
            ->build();

        $queryString = $query['query']['bool']['must'][1]['query_string'] ?? null;
        $fields = $queryString['fields'] ?? [];

        // Should use standard fields by default
        $this->assertContains('name.nl', $fields);
        $this->assertNotContains('name.nl.decompounder', $fields);
    }

    /**
     * @test
     */
    public function it_maintains_postal_codes_without_decompounder(): void
    {
        $queryBuilder = new ElasticSearchOfferQueryBuilder(
            aggregationSize: 10,
            useWordBreaker: true
        );

        $query = $queryBuilder
            ->withStartAndLimit(new Start(0), new Limit(10))
            ->withAdvancedQuery(
                new LuceneQueryString('1000'),
                new Language('nl'),
                new Language('fr')
            )
            ->build();

        $fields = $query['query']['bool']['must'][1]['query_string']['fields'] ?? [];

        // Postal codes should remain standard (exact-match)
        $this->assertContains('address.nl.postalCode', $fields);
        $this->assertContains('address.fr.postalCode', $fields);

        // Should NOT have decompounder suffix
        $this->assertNotContains('address.nl.postalCode.decompounder', $fields);
        $this->assertNotContains('address.fr.postalCode.decompounder', $fields);
    }

    /**
     * @test
     */
    public function it_applies_word_breaker_to_multiple_languages(): void
    {
        $queryBuilder = new ElasticSearchOfferQueryBuilder(
            aggregationSize: 10,
            useWordBreaker: true
        );

        $query = $queryBuilder
            ->withStartAndLimit(new Start(0), new Limit(10))
            ->withAdvancedQuery(
                new LuceneQueryString('test'),
                new Language('nl'),
                new Language('fr'),
                new Language('de')
            )
            ->build();

        $fields = $query['query']['bool']['must'][1]['query_string']['fields'] ?? [];

        // All configured languages should have decompounder variants
        $this->assertContains('name.nl.decompounder', $fields);
        $this->assertContains('name.fr.decompounder', $fields);
        $this->assertContains('name.de.decompounder', $fields);

        $this->assertContains('description.nl.decompounder', $fields);
        $this->assertContains('description.fr.decompounder', $fields);
        $this->assertContains('description.de.decompounder', $fields);

        // Standard fields should NOT be present
        $this->assertNotContains('name.nl', $fields);
        $this->assertNotContains('name.fr', $fields);
        $this->assertNotContains('name.de', $fields);
    }

    /**
     * @test
     */
    public function it_builds_valid_query_structure_with_word_breaker(): void
    {
        $queryBuilder = new ElasticSearchOfferQueryBuilder(
            aggregationSize: 10,
            useWordBreaker: true
        );

        $query = $queryBuilder
            ->withStartAndLimit(new Start(0), new Limit(10))
            ->withAdvancedQuery(
                new LuceneQueryString('parkbegraafplaats'),
                new Language('nl')
            )
            ->build();

        // Verify overall query structure
        $this->assertIsArray($query);
        $this->assertArrayHasKey('query', $query);
        $this->assertArrayHasKey('bool', $query['query']);
        $this->assertArrayHasKey('must', $query['query']['bool']);

        // Verify query_string is present
        $queryString = $query['query']['bool']['must'][1]['query_string'] ?? null;
        $this->assertNotNull($queryString);
        $this->assertEquals('parkbegraafplaats', $queryString['query']);

        // Verify fields array exists and contains decompounder fields
        $this->assertArrayHasKey('fields', $queryString);
        $this->assertIsArray($queryString['fields']);
        $this->assertNotEmpty($queryString['fields']);
        $this->assertContains('name.nl.decompounder', $queryString['fields']);
    }
}
