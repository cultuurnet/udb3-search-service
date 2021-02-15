<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
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
use CultuurNet\UDB3\Search\Offer\Status;
use CultuurNet\UDB3\Search\Offer\SubEventQueryParameters;
use CultuurNet\UDB3\Search\Offer\TermId;
use CultuurNet\UDB3\Search\Offer\TermLabel;
use CultuurNet\UDB3\Search\Offer\WorkflowStatus;
use CultuurNet\UDB3\Search\PriceInfo\Price;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\CardinalityAggregation;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoBoundingBoxQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoShapeQuery;
use ValueObjects\Geography\Country;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

final class ElasticSearchOfferQueryBuilder extends AbstractElasticSearchQueryBuilder implements
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

    public function withCdbIdFilter(Cdbid $cdbid): self
    {
        return $this->withMatchQuery('id', $cdbid->toNative());
    }

    public function withLocationCdbIdFilter(Cdbid $locationCdbid): self
    {
        return $this->withMatchQuery('location.id', $locationCdbid->toNative());
    }

    public function withOrganizerCdbIdFilter(Cdbid $organizerCdbId): self
    {
        return $this->withMatchQuery('organizer.id', $organizerCdbId);
    }

    public function withMainLanguageFilter(Language $mainLanguages): self
    {
        return $this->withMatchQuery('mainLanguage', $mainLanguages->getCode());
    }

    public function withLanguageFilter(Language $language): self
    {
        return $this->withMatchQuery('languages', $language->getCode());
    }

    public function withCompletedLanguageFilter(Language $language): self
    {
        return $this->withMatchQuery('completedLanguages', $language->getCode());
    }

    public function withAvailableRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ): self {
        $this->guardDateRange('available', $from, $to);
        return $this->withDateRangeQuery('availableRange', $from, $to);
    }

    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses): self
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
    ): self {
        $this->guardDateRange('created', $from, $to);
        return $this->withDateRangeQuery('created', $from, $to);
    }

    public function withModifiedRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ): self {
        $this->guardDateRange('modified', $from, $to);
        return $this->withDateRangeQuery('modified', $from, $to);
    }

    public function withCreatorFilter(Creator $creator): self
    {
        return $this->withMatchQuery('creator', $creator->toNative());
    }

    public function withDateRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ): self {
        $this->guardDateRange('date', $from, $to);
        return $this->withDateRangeQuery('dateRange', $from, $to);
    }

    public function withLocalTimeRangeFilter(int $localTimeFrom = null, int $localTimeTo = null): self
    {
        $this->guardNaturalIntegerRange('localTime', new Natural($localTimeFrom), new Natural($localTimeTo));
        return $this->withRangeQuery('localTimeRange', $localTimeFrom, $localTimeTo);
    }

    public function withStatusFilter(Status ...$statuses): self
    {
        return $this->withMultiValueMatchQuery(
            'status',
            array_map(
                function (Status $status) {
                    return $status->toNative();
                },
                $statuses
            )
        );
    }

    public function withSubEventFilter(SubEventQueryParameters $subEventQueryParameters): self
    {
        $from = $subEventQueryParameters->getDateFrom();
        $to = $subEventQueryParameters->getDateTo();
        $localTimeFrom = $subEventQueryParameters->getLocalTimeFrom();
        $localTimeTo = $subEventQueryParameters->getLocalTimeTo();
        $statuses = $subEventQueryParameters->getStatuses();

        $this->guardDateRange('date', $from, $to);

        if ($localTimeFrom && $localTimeTo) {
            $this->guardNaturalIntegerRange('localTime', new Natural($localTimeFrom), new Natural($localTimeTo));
        }

        $queries = [];

        if ($from || $to) {
            $queries[] = $this->createRangeQuery(
                'subEvent.dateRange',
                $from ? $from->format(DATE_ATOM) : null,
                $to ? $to->format(DATE_ATOM) : null
            );
        }

        if ($localTimeFrom || $localTimeTo) {
            $queries[] = $this->createRangeQuery(
                'subEvent.localTimeRange',
                $subEventQueryParameters->getLocalTimeFrom(),
                $subEventQueryParameters->getLocalTimeTo()
            );
        }

        if (count($statuses) > 0) {
            $queries[] = $this->createMultiValueMatchQuery(
                'subEvent.status',
                array_map(
                    function (Status $status) {
                        return $status->toNative();
                    },
                    $statuses
                )
            );
        }

        return $this->withBooleanFilterQueryOnNestedObject(
            'subEvent',
            ...$queries
        );
    }

    public function withCalendarTypeFilter(CalendarType ...$calendarTypes): self
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

    public function withPostalCodeFilter(PostalCode $postalCode): self
    {
        return $this->withMultiFieldMatchQuery(
            (new KnownLanguages())->fieldNames(
                'address.{{lang}}.postalCode'
            ),
            $postalCode->toNative()
        );
    }

    public function withAddressCountryFilter(Country $country): self
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
    ): self {
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

    public function withGeoDistanceFilter(GeoDistanceParameters $geoDistanceParameters): self
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

    public function withAudienceTypeFilter(AudienceType $audienceType): self
    {
        return $this->withMatchQuery('audienceType', $audienceType->toNative());
    }

    public function withAgeRangeFilter(Natural $minimum = null, Natural $maximum = null): self
    {
        $this->guardNaturalIntegerRange('age', $minimum, $maximum);

        $minimum = is_null($minimum) ? null : $minimum->toNative();
        $maximum = is_null($maximum) ? null : $maximum->toNative();

        return $this->withRangeQuery('typicalAgeRange', $minimum, $maximum);
    }

    public function withAllAgesFilter($include): self
    {
        return $this->withTermQuery('allAges', (bool) $include);
    }

    public function withPriceRangeFilter(Price $minimum = null, Price $maximum = null): self
    {
        $this->guardNaturalIntegerRange('price', $minimum, $maximum);

        $minimum = is_null($minimum) ? null : $minimum->toFloat();
        $maximum = is_null($maximum) ? null : $maximum->toFloat();

        return $this->withRangeQuery('price', $minimum, $maximum);
    }

    public function withMediaObjectsFilter($include): self
    {
        $min = $include ? 1 : null;
        $max = $include ? null : 0;

        return $this->withRangeQuery('mediaObjectsCount', $min, $max);
    }

    public function withUiTPASFilter($include): self
    {
        $uitpasQuery = 'organizer.labels:(UiTPAS* OR Paspartoe)';

        if (!$include) {
            $uitpasQuery = "!({$uitpasQuery})";
        }

        return $this->withQueryStringQuery($uitpasQuery, [], BoolQuery::FILTER);
    }

    public function withTermIdFilter(TermId $termId): self
    {
        return $this->withMatchQuery('terms.id', $termId->toNative());
    }

    public function withTermLabelFilter(TermLabel $termLabel): self
    {
        return $this->withMatchQuery('terms.label', $termLabel->toNative());
    }

    public function withLocationTermIdFilter(TermId $locationTermId): self
    {
        return $this->withMatchQuery('location.terms.id', $locationTermId->toNative());
    }

    public function withLocationTermLabelFilter(TermLabel $locationTermLabel): self
    {
        return $this->withMatchQuery('location.terms.label', $locationTermLabel->toNative());
    }

    public function withLabelFilter(LabelName $label): self
    {
        return $this->withMatchQuery('labels', $label->toNative());
    }

    public function withLocationLabelFilter(LabelName $locationLabel): self
    {
        return $this->withMatchQuery('location.labels', $locationLabel->toNative());
    }

    public function withOrganizerLabelFilter(LabelName $organizerLabel): self
    {
        return $this->withMatchQuery('organizer.labels', $organizerLabel->toNative());
    }

    public function withDuplicateFilter(bool $isDuplicate): self
    {
        return $this->withTermQuery('isDuplicate', (bool) $isDuplicate);
    }

    public function withProductionIdFilter(string $productionId): self
    {
        return $this->withMatchQuery('production.id', $productionId);
    }

    public function withFacet(FacetName $facetName): self
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

    public function withSortByScore(SortOrder $sortOrder): self
    {
        return $this->withFieldSort('_score', $sortOrder->toNative());
    }

    public function withSortByAvailableTo(SortOrder $sortOrder): self
    {
        return $this->withFieldSort('availableTo', $sortOrder->toNative());
    }

    public function withSortByCreated(SortOrder $sortOrder): self
    {
        return $this->withFieldSort('created', $sortOrder->toNative());
    }

    public function withSortByModified(SortOrder $sortOrder): self
    {
        return $this->withFieldSort('modified', $sortOrder->toNative());
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/guide/current/sorting-by-distance.html
     */
    public function withSortByDistance(Coordinates $coordinates, SortOrder $sortOrder): self
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

    public function withSortByPopularity(SortOrder $sortOrder): self
    {
        return $this->withFieldSort('metadata.popularity', $sortOrder->toNative());
    }

    public function withGroupByProductionId(): self
    {
        $c = clone $this;
        $c->extraQueryParameters['collapse']['field'] = 'productionCollapseValue';

        // Add a "total" aggregation based on the number of results with a distinct value for productionCollapseValue
        // to calculate the correct number of total results. (The normal total number of hits is unaffected by a
        // collapse. See https://www.elastic.co/guide/en/elasticsearch/reference/6.8/search-request-collapse.html)
        $aggregation = new CardinalityAggregation('total');
        $aggregation->setField('productionCollapseValue');
        $c->search->addAggregation($aggregation);

        return $c;
    }
}
