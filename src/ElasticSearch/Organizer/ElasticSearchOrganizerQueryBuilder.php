<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\SortBuilders;
use InvalidArgumentException;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Url;
use CultuurNet\UDB3\Search\ElasticSearch\KnownLanguages;
use CultuurNet\UDB3\Search\ElasticSearch\PredefinedQueryFieldsInterface;
use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoBoundingBoxQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoShapeQuery;

final class ElasticSearchOrganizerQueryBuilder extends AbstractElasticSearchQueryBuilder implements
    OrganizerQueryBuilderInterface
{
    private PredefinedQueryFieldsInterface $predefinedQueryStringFields;

    private ?int $aggregationSize;

    public function __construct(int $aggregationSize = null)
    {
        parent::__construct();
        $this->extraQueryParameters['_source'] = ['@id', '@type', 'originalEncodedJsonLd', 'regions'];
        $this->aggregationSize = $aggregationSize;
        $this->predefinedQueryStringFields = new OrganizerPredefinedQueryStringFields();
    }

    protected function getPredefinedQueryStringFields(Language ...$languages): array
    {
        return $this->predefinedQueryStringFields->getPredefinedFields(...$languages);
    }

    public function withIdFilter(string $organizerId): self
    {
        return $this->withMatchQuery('id', $organizerId);
    }

    public function withAutoCompleteFilter(string $input): ElasticSearchOrganizerQueryBuilder
    {
        // Currently not translatable, just look in the Dutch version for now.
        return $this->withMatchPhraseQuery('name.nl.autocomplete', $input);
    }

    public function withWebsiteFilter(string $url): ElasticSearchOrganizerQueryBuilder
    {
        // Try to normalize the URL to return as many relevant results as possible.
        // If the URL could not be parsed, use it as given. This may result in 0 results if the URL is really invalid,
        // but that is logical since no organizers have that URL then. And in the case that an (older) organizer has
        // an invalid URL, this still makes it possible to look it up.
        try {
            $urlObject = new Url($url);
            $normalizedUrl = $urlObject->getNormalizedUrl();
        } catch (InvalidArgumentException $e) {
            $normalizedUrl = $url;
        }

        return $this->withMatchQuery('url', $normalizedUrl);
    }

    public function withDomainFilter(string $domain): ElasticSearchOrganizerQueryBuilder
    {
        if (strpos($domain, 'www.') === 0) {
            $domain = substr($domain, strlen('www.'));
        }
        return $this->withTermQuery('domain', $domain);
    }

    public function withPostalCodeFilter(PostalCode $postalCode): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withMultiFieldMatchQuery(
            (new KnownLanguages())->fieldNames(
                'address.{{lang}}.postalCode'
            ),
            $postalCode->toString()
        );
    }

    public function withAddressCountryFilter(Country $country): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withMultiFieldMatchQuery(
            (new KnownLanguages())->fieldNames(
                'address.{{lang}}.addressCountry'
            ),
            $country->toString()
        );
    }

    public function withRegionFilter(
        string $regionIndexName,
        string $regionDocumentType,
        RegionId $regionId
    ): self {
        $geoShapeQuery = new GeoShapeQuery();

        $geoShapeQuery->addPreIndexedShape(
            'geo',
            $regionId->toString(),
            $regionDocumentType,
            $regionIndexName,
            'location'
        );

        $c = $this->getClone();
        $c->boolQuery->add($geoShapeQuery, BoolQuery::FILTER);
        return $c;
    }

    public function withGeoDistanceFilter(GeoDistanceParameters $geoDistanceParameters): self
    {
        $geoDistanceQuery = new GeoDistanceQuery(
            'geo_point',
            $geoDistanceParameters->getMaximumDistance()->toString(),
            (object) [
                'lat' => $geoDistanceParameters->getCoordinates()->getLatitude()->toDouble(),
                'lon' => $geoDistanceParameters->getCoordinates()->getLongitude()->toDouble(),
            ]
        );

        $c = $this->getClone();
        $c->boolQuery->add($geoDistanceQuery, BoolQuery::FILTER);
        return $c;
    }

    public function withGeoBoundsFilter(GeoBoundsParameters $geoBoundsParameters): self
    {
        $northWest = $geoBoundsParameters->getNorthWestCoordinates();
        $southEast = $geoBoundsParameters->getSouthEastCoordinates();

        $topLeft = [
            'lat' => $northWest->getLatitude()->toDouble(),
            'lon' => $northWest->getLongitude()->toDouble(),
        ];

        $bottomRight = [
            'lat' => $southEast->getLatitude()->toDouble(),
            'lon' => $southEast->getLongitude()->toDouble(),
        ];

        $geoBoundingBoxQuery = new GeoBoundingBoxQuery('geo_point', [$topLeft, $bottomRight]);

        $c = $this->getClone();
        $c->boolQuery->add($geoBoundingBoxQuery, BoolQuery::FILTER);
        return $c;
    }

    public function withImagesFilter(bool $include): self
    {
        $min = $include ? 1 : null;
        $max = $include ? null : 0;

        return $this->withRangeQuery('imagesCount', $min, $max);
    }

    public function withCreatorFilter(Creator $creator): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withMatchQuery('creator', $creator->toString());
    }

    public function withLabelFilter(LabelName $label): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withMatchQuery('labels', $label->toString());
    }

    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withMultiValueMatchQuery(
            'workflowStatus',
            array_map(
                fn (WorkflowStatus $workflowStatus): string => $workflowStatus->toString(),
                $workflowStatuses
            )
        );
    }

    public function withContributorsFilter(string $email): OrganizerQueryBuilderInterface
    {
        return $this->withMatchQuery('contributors', $email);
    }

    public function withFacet(FacetName $facetName): self
    {
        $facetFields = [
            FacetName::regions()->toString() => 'regions.keyword',
        ];

        if (!isset($facetFields[$facetName->toString()])) {
            return $this;
        }

        $facetField = $facetFields[$facetName->toString()];
        $aggregation = new TermsAggregation($facetName->toString(), $facetField);

        if (null !== $this->aggregationSize) {
            $aggregation->addParameter('size', $this->aggregationSize);
        }

        $c = $this->getClone();
        $c->search->addAggregation($aggregation);
        return $c;
    }

    public function withSortByScore(SortOrder $sortOrder): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withFieldSort('_score', $sortOrder->toString());
    }

    public function withSortByCompleteness(SortOrder $sortOrder): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withFieldSort('completeness', $sortOrder->toString());
    }

    public function withSortByCreated(SortOrder $sortOrder): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withFieldSort('created', $sortOrder->toString());
    }

    public function withSortByModified(SortOrder $sortOrder): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withFieldSort('modified', $sortOrder->toString());
    }

    public function withSortBuilders(array $sorts, array $sortBuilders): OrganizerQueryBuilderInterface
    {
        return (new SortBuilders($this))->build($sorts, $sortBuilders);
    }
}
