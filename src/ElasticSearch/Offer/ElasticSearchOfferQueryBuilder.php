<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\PriceInfo\Price;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\KnownLanguages;
use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Offer\AudienceType;
use CultuurNet\UDB3\Search\Offer\CalendarType;
use CultuurNet\UDB3\Search\Offer\Cdbid;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\TermId;
use CultuurNet\UDB3\Search\Offer\TermLabel;
use CultuurNet\UDB3\Search\Offer\WorkflowStatus;
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

    /**
     * @inheritdoc
     */
    public function __construct(int $aggregationSize = null)
    {
        parent::__construct();
        
        $this->predefinedQueryStringFields = new OfferPredefinedQueryStringFields();
        $this->aggregationSize = $aggregationSize;
    }

    /**
     * @inheritdoc
     */
    protected function getPredefinedQueryStringFields(Language ...$languages)
    {
        return $this->predefinedQueryStringFields->getPredefinedFields(...$languages);
    }

    /**
     * @inheritdoc
     */
    public function withCdbIdFilter(Cdbid $cdbid)
    {
        return $this->withMatchQuery('id', $cdbid->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withLocationCdbIdFilter(Cdbid $locationCdbid)
    {
        return $this->withMatchQuery('location.id', $locationCdbid->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withOrganizerCdbIdFilter(Cdbid $organizerCdbId)
    {
        return $this->withMatchQuery('organizer.id', $organizerCdbId);
    }

    /**
     * @inheritdoc
     */
    public function withMainLanguageFilter(Language $mainLanguages)
    {
        return $this->withMatchQuery('mainLanguage', $mainLanguages->getCode());
    }

    /**
     * @inheritdoc
     */
    public function withLanguageFilter(Language $language)
    {
        return $this->withMatchQuery('languages', $language->getCode());
    }

    /**
     * @inheritdoc
     */
    public function withCompletedLanguageFilter(Language $language)
    {
        return $this->withMatchQuery('completedLanguages', $language->getCode());
    }

    /**
     * @inheritdoc
     */
    public function withAvailableRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ) {
        $this->guardDateRange('available', $from, $to);
        return $this->withDateRangeQuery('availableRange', $from, $to);
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function withCreatedRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ) {
        $this->guardDateRange('created', $from, $to);
        return $this->withDateRangeQuery('created', $from, $to);
    }

    /**
     * @inheritdoc
     */
    public function withModifiedRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ) {
        $this->guardDateRange('modified', $from, $to);
        return $this->withDateRangeQuery('modified', $from, $to);
    }

    /**
     * @inheritdoc
     */
    public function withCreatorFilter(Creator $creator)
    {
        return $this->withMatchQuery('creator', $creator->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withDateRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ) {
        $this->guardDateRange('date', $from, $to);
        return $this->withDateRangeQuery('dateRange', $from, $to);
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function withPostalCodeFilter(PostalCode $postalCode)
    {
        return $this->withMultiFieldMatchQuery(
            (new KnownLanguages())->fieldNames(
                'address.{{lang}}.postalCode'
            ),
            $postalCode->toNative()
        );
    }

    /**
     * @inheritdoc
     */
    public function withAddressCountryFilter(Country $country)
    {
        return $this->withMultiFieldMatchQuery(
            (new KnownLanguages())->fieldNames(
                'address.{{lang}}.addressCountry'
            ),
            $country->getCode()->toNative()
        );
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function withAudienceTypeFilter(AudienceType $audienceType)
    {
        return $this->withMatchQuery('audienceType', $audienceType->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withAgeRangeFilter(Natural $minimum = null, Natural $maximum = null)
    {
        $this->guardNaturalIntegerRange('age', $minimum, $maximum);

        $minimum = is_null($minimum) ? null : $minimum->toNative();
        $maximum = is_null($maximum) ? null : $maximum->toNative();

        return $this->withRangeQuery('typicalAgeRange', $minimum, $maximum);
    }

    /**
     * @inheritdoc
     */
    public function withAllAgesFilter($include)
    {
        return $this->withTermQuery('allAges', (bool) $include);
    }

    /**
     * @inheritdoc
     */
    public function withPriceRangeFilter(Price $minimum = null, Price $maximum = null)
    {
        $this->guardNaturalIntegerRange('price', $minimum, $maximum);

        $minimum = is_null($minimum) ? null : $minimum->toFloat();
        $maximum = is_null($maximum) ? null : $maximum->toFloat();

        return $this->withRangeQuery('price', $minimum, $maximum);
    }

    /**
     * @inheritdoc
     */
    public function withMediaObjectsFilter($include)
    {
        $min = $include ? 1 : null;
        $max = $include ? null : 0;

        return $this->withRangeQuery('mediaObjectsCount', $min, $max);
    }

    /**
     * @inheritdoc
     */
    public function withUiTPASFilter($include)
    {
        $uitpasQuery = 'organizer.labels:(UiTPAS* OR Paspartoe)';

        if (!$include) {
            $uitpasQuery = "!({$uitpasQuery})";
        }

        return $this->withQueryStringQuery($uitpasQuery, [], BoolQuery::FILTER);
    }

    /**
     * @inheritdoc
     */
    public function withTermIdFilter(TermId $termId)
    {
        return $this->withMatchQuery('terms.id', $termId->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withTermLabelFilter(TermLabel $termLabel)
    {
        return $this->withMatchQuery('terms.label', $termLabel->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withLocationTermIdFilter(TermId $locationTermId)
    {
        return $this->withMatchQuery('location.terms.id', $locationTermId->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withLocationTermLabelFilter(TermLabel $locationTermLabel)
    {
        return $this->withMatchQuery('location.terms.label', $locationTermLabel->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withLabelFilter(LabelName $label)
    {
        return $this->withMatchQuery('labels', $label->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withLocationLabelFilter(LabelName $locationLabel)
    {
        return $this->withMatchQuery('location.labels', $locationLabel->toNative());
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function withSortByScore(SortOrder $sortOrder)
    {
        return $this->withFieldSort('_score', $sortOrder->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withSortByAvailableTo(SortOrder $sortOrder)
    {
        return $this->withFieldSort('availableTo', $sortOrder->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withSortByCreated(SortOrder $sortOrder)
    {
        return $this->withFieldSort('created', $sortOrder->toNative());
    }

    /**
     * @inheritdoc
     */
    public function withSortByModified(SortOrder $sortOrder)
    {
        return $this->withFieldSort('modified', $sortOrder->toNative());
    }

    /**
     * @inheritdoc
     *
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
