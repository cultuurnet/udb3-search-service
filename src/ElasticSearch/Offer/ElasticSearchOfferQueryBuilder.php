<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\ElasticSearch\PredefinedQueryFieldsInterface;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\KnownLanguages;
use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Offer\Age;
use CultuurNet\UDB3\Search\Offer\AudienceType;
use CultuurNet\UDB3\Search\Offer\CalendarType;
use CultuurNet\UDB3\Search\Offer\Cdbid;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\Status;
use CultuurNet\UDB3\Search\Offer\SubEventQueryParameters;
use CultuurNet\UDB3\Search\Offer\TermId;
use CultuurNet\UDB3\Search\Offer\TermLabel;
use CultuurNet\UDB3\Search\Offer\Time;
use CultuurNet\UDB3\Search\Offer\WorkflowStatus;
use CultuurNet\UDB3\Search\PriceInfo\Price;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use DateTimeImmutable;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\CardinalityAggregation;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoBoundingBoxQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoShapeQuery;

final class ElasticSearchOfferQueryBuilder extends AbstractElasticSearchQueryBuilder implements
    OfferQueryBuilderInterface
{
    private PredefinedQueryFieldsInterface $predefinedQueryStringFields;

    /**
     * Size to be used for term aggregations.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-terms-aggregation.html#search-aggregations-bucket-terms-aggregation-size
     */
    private ?int $aggregationSize;

    public function __construct(int $aggregationSize = null)
    {
        parent::__construct();

        $this->predefinedQueryStringFields = new OfferPredefinedQueryStringFields();
        $this->aggregationSize = $aggregationSize;

        $this->extraQueryParameters['_source'] = ['@id', '@type', 'originalEncodedJsonLd', 'regions'];
    }

    /**
     * @return string[]
     */
    protected function getPredefinedQueryStringFields(Language ...$languages): array
    {
        return $this->predefinedQueryStringFields->getPredefinedFields(...$languages);
    }

    public function withCdbIdFilter(Cdbid $cdbid): self
    {
        return $this->withMatchQuery('id', $cdbid->toString());
    }

    public function withLocationCdbIdFilter(Cdbid $locationCdbid): self
    {
        return $this->withMatchQuery('location.id', $locationCdbid->toString());
    }

    public function withOrganizerCdbIdFilter(Cdbid $organizerCdbId): self
    {
        return $this->withMatchQuery('organizer.id', $organizerCdbId->toString());
    }

    public function withMainLanguageFilter(Language $mainLanguage): self
    {
        return $this->withMatchQuery('mainLanguage', $mainLanguage->getCode());
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
        DateTimeImmutable $from = null,
        DateTimeImmutable $to = null
    ): self {
        $this->guardDateRange('available', $from, $to);
        return $this->withDateRangeQuery('availableRange', $from, $to);
    }

    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses): self
    {
        return $this->withMultiValueMatchQuery(
            'workflowStatus',
            array_map(
                static function (WorkflowStatus $workflowStatus) {
                    return $workflowStatus->toString();
                },
                $workflowStatuses
            )
        );
    }

    public function withCreatedRangeFilter(
        DateTimeImmutable $from = null,
        DateTimeImmutable $to = null
    ): self {
        $this->guardDateRange('created', $from, $to);
        return $this->withDateRangeQuery('created', $from, $to);
    }

    public function withModifiedRangeFilter(
        DateTimeImmutable $from = null,
        DateTimeImmutable $to = null
    ): self {
        $this->guardDateRange('modified', $from, $to);
        return $this->withDateRangeQuery('modified', $from, $to);
    }

    public function withCreatorFilter(Creator $creator): self
    {
        return $this->withMatchQuery('creator', $creator->toString());
    }

    public function withDateRangeFilter(
        DateTimeImmutable $from = null,
        DateTimeImmutable $to = null
    ): self {
        $this->guardDateRange('date', $from, $to);
        return $this->withDateRangeQuery('dateRange', $from, $to);
    }

    public function withLocalTimeRangeFilter(int $localTimeFrom = null, int $localTimeTo = null): self
    {
        $this->guardNaturalIntegerRange('localTime', new Time($localTimeFrom), new Time($localTimeTo));
        return $this->withRangeQuery('localTimeRange', $localTimeFrom, $localTimeTo);
    }

    public function withStatusFilter(Status ...$statuses): self
    {
        return $this->withMultiValueMatchQuery(
            'status',
            array_map(
                static function (Status $status) {
                    return $status->toString();
                },
                $statuses
            )
        );
    }

    public function withBookingAvailabilityFilter(string $bookingAvailability): self
    {
        return $this->withMatchQuery('bookingAvailability', $bookingAvailability);
    }

    public function withSubEventFilter(SubEventQueryParameters $subEventQueryParameters): self
    {
        $from = $subEventQueryParameters->getDateFrom();
        $to = $subEventQueryParameters->getDateTo();
        $localTimeFrom = $subEventQueryParameters->getLocalTimeFrom();
        $localTimeTo = $subEventQueryParameters->getLocalTimeTo();
        $statuses = $subEventQueryParameters->getStatuses();
        $bookingAvailability = $subEventQueryParameters->getBookingAvailability();

        $this->guardDateRange('date', $from, $to);

        if ($localTimeFrom && $localTimeTo) {
            $this->guardNaturalIntegerRange('localTime', new Time($localTimeFrom), new Time($localTimeTo));
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
                    static function (Status $status) {
                        return $status->toString();
                    },
                    $statuses
                )
            );
        }

        if ($bookingAvailability !== null) {
            $queries[] = new MatchQuery('subEvent.bookingAvailability', $bookingAvailability);
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
                static function (CalendarType $calendarType) {
                    return $calendarType->toString();
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
            $postalCode->toString()
        );
    }

    public function withAddressCountryFilter(Country $country): self
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

    public function withAudienceTypeFilter(AudienceType $audienceType): self
    {
        return $this->withMatchQuery('audienceType', $audienceType->toString());
    }

    public function withAgeRangeFilter(Age $minimum = null, Age $maximum = null): self
    {
        $this->guardNaturalIntegerRange('age', $minimum, $maximum);

        $minimum = is_null($minimum) ? null : $minimum->toNative();
        $maximum = is_null($maximum) ? null : $maximum->toNative();

        return $this->withRangeQuery('typicalAgeRange', $minimum, $maximum);
    }

    public function withAllAgesFilter(bool $include): self
    {
        return $this->withTermQuery('allAges', $include);
    }

    public function withPriceRangeFilter(Price $minimum = null, Price $maximum = null): self
    {
        $this->guardNaturalIntegerRange('price', $minimum, $maximum);

        $minimum = is_null($minimum) ? null : $minimum->toFloat();
        $maximum = is_null($maximum) ? null : $maximum->toFloat();

        return $this->withRangeQuery('price', $minimum, $maximum);
    }

    public function withMediaObjectsFilter(bool $include): self
    {
        $min = $include ? 1 : null;
        $max = $include ? null : 0;

        return $this->withRangeQuery('mediaObjectsCount', $min, $max);
    }

    public function withVideosFilter(bool $include): self
    {
        $min = $include ? 1 : null;
        $max = $include ? null : 0;

        return $this->withRangeQuery('videosCount', $min, $max);
    }

    public function withUiTPASFilter(bool $include): self
    {
        $uitpasQuery = 'organizer.labels:(UiTPAS* OR Paspartoe)';

        if (!$include) {
            $uitpasQuery = "!({$uitpasQuery})";
        }

        return $this->withQueryStringQuery($uitpasQuery, [], BoolQuery::FILTER);
    }

    public function withTermIdFilter(TermId $termId): self
    {
        return $this->withMatchQuery('terms.id', $termId->toString());
    }

    public function withTermLabelFilter(TermLabel $termLabel): self
    {
        return $this->withMatchQuery('terms.label', $termLabel->toString());
    }

    public function withLocationTermIdFilter(TermId $locationTermId): self
    {
        return $this->withMatchQuery('location.terms.id', $locationTermId->toString());
    }

    public function withLocationTermLabelFilter(TermLabel $locationTermLabel): self
    {
        return $this->withMatchQuery('location.terms.label', $locationTermLabel->toString());
    }

    public function withLabelFilter(LabelName $label): self
    {
        return $this->withMatchQuery('labels', $label->toString());
    }

    public function withLocationLabelFilter(LabelName $locationLabel): self
    {
        return $this->withMatchQuery('location.labels', $locationLabel->toString());
    }

    public function withOrganizerLabelFilter(LabelName $organizerLabel): self
    {
        return $this->withMatchQuery('organizer.labels', $organizerLabel->toString());
    }

    public function withDuplicateFilter(bool $isDuplicate): self
    {
        return $this->withTermQuery('isDuplicate', $isDuplicate);
    }

    public function withProductionIdFilter(string $productionId): self
    {
        return $this->withMatchQuery('production.id', $productionId);
    }

    public function withRecommendationForFilter(Cdbid $eventId): self
    {
        return $this->withMatchQuery('metadata.recommendationFor.event', $eventId->toString());
    }

    public function withFacet(FacetName $facetName): self
    {
        $facetFields = [
            FacetName::regions()->toString() => 'regions.keyword',
            FacetName::types()->toString() => 'typeIds',
            FacetName::themes()->toString() => 'themeIds',
            FacetName::facilities()->toString() => 'facilityIds',
            FacetName::labels()->toString() => 'labels.keyword',
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

    public function withSortByScore(SortOrder $sortOrder): self
    {
        return $this->withFieldSort('_score', $sortOrder->toString());
    }

    public function withSortByAvailableTo(SortOrder $sortOrder): self
    {
        return $this->withFieldSort('availableTo', $sortOrder->toString());
    }

    public function withSortByCreated(SortOrder $sortOrder): self
    {
        return $this->withFieldSort('created', $sortOrder->toString());
    }

    public function withSortByModified(SortOrder $sortOrder): self
    {
        return $this->withFieldSort('modified', $sortOrder->toString());
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/guide/current/sorting-by-distance.html
     */
    public function withSortByDistance(Coordinates $coordinates, SortOrder $sortOrder): self
    {
        return $this->withFieldSort(
            '_geo_distance',
            $sortOrder->toString(),
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
        return $this->withFieldSort('metadata.popularity', $sortOrder->toString());
    }

    public function withSortByRecommendationScore(SortOrder $sortOrder): self
    {
        return $this->withFieldSort('metadata.recommendationFor.score', $sortOrder->toString());
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
