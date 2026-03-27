<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\Language\Language;
use PHPUnit\Framework\TestCase;

final class OfferPredefinedQueryStringFieldsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_predefined_query_string_fields(): void
    {
        $fields = (new OfferPredefinedQueryStringFields())->getPredefinedFields(
            new Language('nl'),
            new Language('fr')
        );

        $this->assertEquals(
            [
                'id',
                'labels_free_text',
                'terms_free_text.id',
                'terms_free_text.label',
                'performer_free_text.name',
                'location.id',
                'organizer.id',
                'name.nl',
                'description.nl',
                'address.nl.addressLocality',
                'address.nl.postalCode',
                'address.nl.streetAddress',
                'location.name.nl',
                'organizer.name.nl',
                'name.fr',
                'description.fr',
                'address.fr.addressLocality',
                'address.fr.postalCode',
                'address.fr.streetAddress',
                'location.name.fr',
                'organizer.name.fr',
            ],
            $fields
        );
    }

    /**
     * @test
     */
    public function it_returns_predefined_query_string_fields_with_word_breaker(): void
    {
        $fields = (new OfferPredefinedQueryStringFields(useWordBreaker: true))->getPredefinedFields(
            new Language('nl'),
            new Language('fr')
        );

        $this->assertEquals(
            [
                'id',
                'labels_free_text.decompounder',
                'terms_free_text.id',
                'terms_free_text.label.decompounder',
                'performer_free_text.name.decompounder',
                'location.id',
                'organizer.id',
                'name.nl.decompounder',
                'description.nl.decompounder',
                'address.nl.addressLocality.decompounder',
                'address.nl.postalCode',
                'address.nl.streetAddress.decompounder',
                'location.name.nl.decompounder',
                'organizer.name.nl.decompounder',
                'name.fr.decompounder',
                'description.fr.decompounder',
                'address.fr.addressLocality.decompounder',
                'address.fr.postalCode',
                'address.fr.streetAddress.decompounder',
                'location.name.fr.decompounder',
                'organizer.name.fr.decompounder',
            ],
            $fields
        );
    }

    /**
     * @test
     */
    public function it_does_not_add_decompounder_to_id_fields_when_word_breaker_enabled(): void
    {
        $fields = (new OfferPredefinedQueryStringFields(useWordBreaker: true))->getPredefinedFields(
            new Language('nl')
        );

        // ID fields should never have decompounder suffix
        $this->assertContains('id', $fields);
        $this->assertContains('terms_free_text.id', $fields);
        $this->assertContains('location.id', $fields);
        $this->assertContains('organizer.id', $fields);

        // Ensure no ID field has decompounder suffix
        $this->assertNotContains('id.decompounder', $fields);
        $this->assertNotContains('terms_free_text.id.decompounder', $fields);
        $this->assertNotContains('location.id.decompounder', $fields);
        $this->assertNotContains('organizer.id.decompounder', $fields);
    }

    /**
     * @test
     */
    public function it_does_not_add_decompounder_to_postal_codes_when_word_breaker_enabled(): void
    {
        $fields = (new OfferPredefinedQueryStringFields(useWordBreaker: true))->getPredefinedFields(
            new Language('nl'),
            new Language('fr')
        );

        // Postal codes should remain exact-match (no decompounder)
        $this->assertContains('address.nl.postalCode', $fields);
        $this->assertContains('address.fr.postalCode', $fields);

        // Ensure postal codes do NOT have decompounder suffix
        $this->assertNotContains('address.nl.postalCode.decompounder', $fields);
        $this->assertNotContains('address.fr.postalCode.decompounder', $fields);
    }

    /**
     * @test
     */
    public function it_adds_decompounder_to_all_text_fields_when_word_breaker_enabled(): void
    {
        $fields = (new OfferPredefinedQueryStringFields(useWordBreaker: true))->getPredefinedFields(
            new Language('nl')
        );

        // All text fields should have decompounder suffix
        $expectedDecompounderFields = [
            'labels_free_text.decompounder',
            'terms_free_text.label.decompounder',
            'performer_free_text.name.decompounder',
            'name.nl.decompounder',
            'description.nl.decompounder',
            'address.nl.addressLocality.decompounder',
            'address.nl.streetAddress.decompounder',
            'location.name.nl.decompounder',
            'organizer.name.nl.decompounder',
        ];

        foreach ($expectedDecompounderFields as $field) {
            $this->assertContains($field, $fields, "Expected field '$field' to be in the list");
        }
    }

    /**
     * @test
     */
    public function it_returns_decompounder_fields_for_multiple_languages(): void
    {
        $fields = (new OfferPredefinedQueryStringFields(useWordBreaker: true))->getPredefinedFields(
            new Language('nl'),
            new Language('fr'),
            new Language('de'),
            new Language('en')
        );

        // Check all languages have decompounder variants
        $languages = ['nl', 'fr', 'de', 'en'];
        foreach ($languages as $lang) {
            $this->assertContains("name.{$lang}.decompounder", $fields);
            $this->assertContains("description.{$lang}.decompounder", $fields);
            $this->assertContains("address.{$lang}.addressLocality.decompounder", $fields);
            $this->assertContains("address.{$lang}.streetAddress.decompounder", $fields);
            $this->assertContains("location.name.{$lang}.decompounder", $fields);
            $this->assertContains("organizer.name.{$lang}.decompounder", $fields);
        }
    }

    /**
     * @test
     */
    public function it_returns_standard_fields_for_multiple_languages_when_word_breaker_disabled(): void
    {
        $fields = (new OfferPredefinedQueryStringFields(useWordBreaker: false))->getPredefinedFields(
            new Language('nl'),
            new Language('fr'),
            new Language('de'),
            new Language('en')
        );

        // Check all languages have standard fields
        $languages = ['nl', 'fr', 'de', 'en'];
        foreach ($languages as $lang) {
            $this->assertContains("name.{$lang}", $fields);
            $this->assertContains("description.{$lang}", $fields);
            $this->assertContains("address.{$lang}.addressLocality", $fields);
            $this->assertContains("address.{$lang}.streetAddress", $fields);
            $this->assertContains("location.name.{$lang}", $fields);
            $this->assertContains("organizer.name.{$lang}", $fields);
        }

        // Ensure NO decompounder fields exist when disabled
        foreach ($languages as $lang) {
            $this->assertNotContains("name.{$lang}.decompounder", $fields);
            $this->assertNotContains("description.{$lang}.decompounder", $fields);
        }
    }

    /**
     * @test
     */
    public function it_defaults_to_word_breaker_disabled(): void
    {
        // Constructor without parameters should default to useWordBreaker = false
        $fieldsDefault = (new OfferPredefinedQueryStringFields())->getPredefinedFields(
            new Language('nl')
        );

        $fieldsExplicitlyDisabled = (new OfferPredefinedQueryStringFields(useWordBreaker: false))->getPredefinedFields(
            new Language('nl')
        );

        // Both should return the same fields (standard fields)
        $this->assertEquals($fieldsExplicitlyDisabled, $fieldsDefault);

        // Neither should contain decompounder fields
        $this->assertNotContains('name.nl.decompounder', $fieldsDefault);
        $this->assertNotContains('name.nl.decompounder', $fieldsExplicitlyDisabled);
    }

    /**
     * @test
     */
    public function it_returns_different_fields_based_on_word_breaker_flag(): void
    {
        $fieldsDisabled = (new OfferPredefinedQueryStringFields(useWordBreaker: false))->getPredefinedFields(
            new Language('nl')
        );

        $fieldsEnabled = (new OfferPredefinedQueryStringFields(useWordBreaker: true))->getPredefinedFields(
            new Language('nl')
        );

        // The lists should be different
        $this->assertNotEquals($fieldsDisabled, $fieldsEnabled);

        // Disabled should have standard fields
        $this->assertContains('name.nl', $fieldsDisabled);
        $this->assertNotContains('name.nl.decompounder', $fieldsDisabled);

        // Enabled should have decompounder fields
        $this->assertContains('name.nl.decompounder', $fieldsEnabled);
        $this->assertNotContains('name.nl', $fieldsEnabled);
    }

    /**
     * @test
     */
    public function it_maintains_field_count_consistency_between_standard_and_decompounder(): void
    {
        $languages = [new Language('nl'), new Language('fr')];

        $fieldsDisabled = (new OfferPredefinedQueryStringFields(useWordBreaker: false))
            ->getPredefinedFields(...$languages);

        $fieldsEnabled = (new OfferPredefinedQueryStringFields(useWordBreaker: true))
            ->getPredefinedFields(...$languages);

        // Both lists should have the same number of fields
        // (decompounder just changes which fields are used, not the count)
        $this->assertCount(count($fieldsDisabled), $fieldsEnabled);
    }
}
