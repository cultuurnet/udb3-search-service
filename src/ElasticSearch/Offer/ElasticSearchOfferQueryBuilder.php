<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\KnownLanguages;
use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Offer\AudienceType;
use CultuurNet\UDB3\Search\Offer\CalendarType;
use CultuurNet\UDB3\Search\Offer\Cdbid;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\TermId;
use CultuurNet\UDB3\Search\Offer\TermLabel;
use CultuurNet\UDB3\Search\Offer\WorkflowStatus;
use CultuurNet\UDB3\Search\PriceInfo\Price;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoBoundingBoxQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoShapeQuery;
use ValueObjects\Geography\Country;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class ElasticSearchOfferQueryBuilder extends AbstractElasticSearchQueryBuilder implements
    OfferQueryBuilderInterface
{
    /**
     * @var \CultuurNet\UDB3\Search\ElasticSearch\PredefinedQueryFieldsInterface
     */
    private $predefinedQueryStringFields;

    /**
     * Size to be used for term aggregations.
     *
     * @var int|null
     *
     * @codingStandardsIgnoreStart
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-terms-aggregation.html#search-aggregations-bucket-terms-aggregation-size
     * @codingStandardsIgnoreEnd
     */
    private $aggregationSize;

    public function __construct(int $aggregationSize = null)
    {
        parent::__construct();

        $this->predefinedQueryStringFields = new OfferPredefinedQueryStringFields();
        $this->aggregationSize = $aggregationSize;
    }

    protected function getPredefinedQueryStringFields(Language ...$languages): array
    {
        return $this->predefinedQueryStringFields->getPredefinedFields(...$languages);
    }

    public function withCdbIdFilter(Cdbid $cdbid)
    {
        return $this->withMatchQuery('id', $cdbid->toNative());
    }

    public function withLocationCdbIdFilter(Cdbid $locationCdbid)
    {
        return $this->withMatchQuery('location.id', $locationCdbid->toNative());
    }

    public function withOrganizerCdbIdFilter(Cdbid $organizerCdbId)
    {
        return $this->withMatchQuery('organizer.id', $organizerCdbId);
    }

    public function withMainLanguageFilter(Language $mainLanguages)
    {
        return $this->withMatchQuery('mainLanguage', $mainLanguages->getCode());
    }

    public function withLanguageFilter(Language $language)
    {
        return $this->withMatchQuery('languages', $language->getCode());
    }

    public function withCompletedLanguageFilter(Language $language)
    {
        return $this->withMatchQuery('completedLanguages', $language->getCode());
    }

    public function withAvailableRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ) {
        $this->guardDateRange('available', $from, $to);
        return $this->withDateRangeQuery('availableRange', $from, $to);
    }

    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses)
    {
        return $this->withMultiValueMatchQuery(
            'workflowStatus',
            array_map(
                function (WorkflowStatus $workflowStatus) {
                    return $workflowStatus->toNative();
                },
                $workflowStatuses
            )
        );
    }

    public function withCreatedRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ) {
        $this->guardDateRange('created', $from, $to);
        return $this->withDateRangeQuery('created', $from, $to);
    }

    public function withModifiedRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ) {
        $this->guardDateRange('modified', $from, $to);
        return $this->withDateRangeQuery('modified', $from, $to);
    }

    public function withCreatorFilter(Creator $creator)
    {
        return $this->withMatchQuery('creator', $creator->toNative());
    }

    public function withDateRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ) {
        $this->guardDateRange('date', $from, $to);
        return $this->withDateRangeQuery('dateRange', $from, $to);
    }

    public function withCalendarTypeFilter(CalendarType ...$calendarTypes)
    {
        return $this->withMultiValueMatchQuery(
            'calendarType',
            array_map(
                function (CalendarType $calendarType) {
                    return $calendarType->toNative();
                },
                $calendarTypes
            )
        );
    }

    public function withPostalCodeFilter(PostalCode $postalCode)
    {
        return $this->withMultiFieldMatchQuery(
            (new KnownLanguages())->fieldNames(
                'address.{{lang}}.postalCode'
            ),
            $postalCode->toNative()
        );
    }

    public function withAddressCountryFilter(Country $country)
    {
        return $this->withMultiFieldMatchQuery(
            (new KnownLanguages())->fieldNames(
                'address.{{lang}}.addressCountry'
            ),
            $country->getCode()->toNative()
        );
    }

    public function withRegionFilter(
        StringLiteral $regionIndexName,
        StringLiteral $regionDocumentType,
        RegionId $regionId
    ) {
        $geoShapeQuery = new GeoShapeQuery();

        $geoShapeQuery->addPreIndexedShape(
            'geo',
            $regionId->toNative(),
            $regionDocumentType->toNative(),
            $regionIndexName->toNative(),
            'location'
        );

        $c = $this->getClone();
        $c->boolQuery->add($geoShapeQuery, BoolQuery::FILTER);
        return $c;
    }

    public function withGeoDistanceFilter(GeoDistanceParameters $geoDistanceParameters)
    {
        $geoDistanceQuery = new GeoDistanceQuery(
            'geo_point',
            $geoDistanceParameters->getMaximumDistance()->toNative(),
            (object) [
                'lat' => $geoDistanceParameters->getCoordinates()->getLatitude()->toDouble(),
                'lon' => $geoDistanceParameters->getCoordinates()->getLongitude()->toDouble(),
            ]
        );

        $c = $this->getClone();
        $c->boolQuery->add($geoDistanceQuery, BoolQuery::FILTER);
        return $c;
    }

    public function withGeoBoundsFilter(GeoBoundsParameters $geoBoundsParameters)
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

    public function withAudienceTypeFilter(AudienceType $audienceType)
    {
        return $this->withMatchQuery('audienceType', $audienceType->toNative());
    }

    public function withAgeRangeFilter(Natural $minimum = null, Natural $maximum = null)
    {
        $this->guardNaturalIntegerRange('age', $minimum, $maximum);

        $minimum = is_null($minimum) ? null : $minimum->toNative();
        $maximum = is_null($maximum) ? null : $maximum->toNative();

        return $this->withRangeQuery('typicalAgeRange', $minimum, $maximum);
    }

    public function withAllAgesFilter($include)
    {
        return $this->withTermQuery('allAges', (bool) $include);
    }

    public function withPriceRangeFilter(Price $minimum = null, Price $maximum = null)
    {
        $this->guardNaturalIntegerRange('price', $minimum, $maximum);

        $minimum = is_null($minimum) ? null : $minimum->toFloat();
        $maximum = is_null($maximum) ? null : $maximum->toFloat();

        return $this->withRangeQuery('price', $minimum, $maximum);
    }

    public function withMediaObjectsFilter($include)
    {
        $min = $include ? 1 : null;
        $max = $include ? null : 0;

        return $this->withRangeQuery('mediaObjectsCount', $min, $max);
    }

    public function withUiTPASFilter($include)
    {
        $uitpasQuery = 'organizer.labels:(UiTPAS* OR Paspartoe)';

        if (!$include) {
            $uitpasQuery = "!({$uitpasQuery})";
        }

        return $this->withQueryStringQuery($uitpasQuery, [], BoolQuery::FILTER);
    }

    public function withTermIdFilter(TermId $termId)
    {
        return $this->withMatchQuery('terms.id', $termId->toNative());
    }

    public function withTermLabelFilter(TermLabel $termLabel)
    {
        return $this->withMatchQuery('terms.label', $termLabel->toNative());
    }

    public function withLocationTermIdFilter(TermId $locationTermId)
    {
        return $this->withMatchQuery('location.terms.id', $locationTermId->toNative());
    }

    public function withLocationTermLabelFilter(TermLabel $locationTermLabel)
    {
        return $this->withMatchQuery('location.terms.label', $locationTermLabel->toNative());
    }

    public function withLabelFilter(LabelName $label)
    {
        return $this->withMatchQuery('labels', $label->toNative());
    }

    public function withLocationLabelFilter(LabelName $locationLabel)
    {
        return $this->withMatchQuery('location.labels', $locationLabel->toNative());
    }

    public function withOrganizerLabelFilter(LabelName $organizerLabel)
    {
        return $this->withMatchQuery('organizer.labels', $organizerLabel->toNative());
    }

    /**
     * @inheritDoc
     */
    public function withDuplicateFilter(bool $isDuplicate)
    {
        return $this->withTermQuery('isDuplicate', (bool) $isDuplicate);
    }

    public function withProductionIdFilter(string $productionId): ElasticSearchOfferQueryBuilder
    {
        return $this->withMatchQuery('production.id', $productionId);
    }

    public function withFacet(FacetName $facetName)
    {
        $facetName = $facetName->toNative();

        $facetFields = [
            FacetName::REGIONS()->toNative() => 'regions.keyword',
            FacetName::TYPES()->toNative() => 'typeIds',
            FacetName::THEMES()->toNative() => 'themeIds',
            FacetName::FACILITIES()->toNative() => 'facilityIds',
            FacetName::LABELS()->toNative() => 'labels.keyword',
        ];

        if (!isset($facetFields[$facetName])) {
            return $this;
        }

        $facetField = $facetFields[$facetName];
        $aggregation = new TermsAggregation($facetName, $facetField);

        if (null !== $this->aggregationSize) {
            $aggregation->addParameter('size', $this->aggregationSize);
        }

        $c = $this->getClone();
        $c->search->addAggregation($aggregation);
        return $c;
    }

    public function withSortByScore(SortOrder $sortOrder)
    {
        return $this->withFieldSort('_score', $sortOrder->toNative());
    }

    public function withSortByAvailableTo(SortOrder $sortOrder)
    {
        return $this->withFieldSort('availableTo', $sortOrder->toNative());
    }

    public function withSortByCreated(SortOrder $sortOrder)
    {
        return $this->withFieldSort('created', $sortOrder->toNative());
    }

    public function withSortByModified(SortOrder $sortOrder)
    {
        return $this->withFieldSort('modified', $sortOrder->toNative());
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/guide/current/sorting-by-distance.html
     */
    public function withSortByDistance(Coordinates $coordinates, SortOrder $sortOrder)
    {
        return $this->withFieldSort(
            '_geo_distance',
            $sortOrder->toNative(),
            [
                'geo_point' => [
                    'lat' => $coordinates->getLatitude()->toDouble(),
                    'lon' => $coordinates->getLongitude()->toDouble(),
                ],
                'unit' => 'km',
                'distance_type' => 'plane',
            ]
        );
    }
}
