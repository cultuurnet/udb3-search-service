<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\PriceInfo\Price;
use CultuurNet\UDB3\Search\QueryBuilder;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;

/**
 * Multiple filters are combined using AND.
 * Filters that accept multiple values use OR internally.
 */
interface OfferQueryBuilderInterface extends QueryBuilder
{
    /**
     * @return OfferQueryBuilderInterface
     */
    public function withCdbIdFilter(Cdbid $cdbid);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withLocationCdbIdFilter(Cdbid $locationCdbid);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withOrganizerCdbIdFilter(Cdbid $organizerCdbId);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withMainLanguageFilter(Language $mainLanguage);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withLanguageFilter(Language $language);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withCompletedLanguageFilter(Language $language);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withAvailableRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    );

    /**
     * @param WorkflowStatus ...$workflowStatuses
     * @return OfferQueryBuilderInterface
     */
    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withCreatedRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    );

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withModifiedRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    );

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withCreatorFilter(Creator $creator);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withDateRangeFilter(
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    );

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withLocalTimeRangeFilter(
        int $localTimeFrom = null,
        int $localTimeTo = null
    );

    /**
     * @param Status ...$statuses
     * @return static
     */
    public function withStatusFilter(Status ...$statuses);

    /**
     * @return static
     */
    public function withBookingAvailabilityFilter(string $bookingAvailability);

    /**
     * @return static
     */
    public function withSubEventFilter(SubEventQueryParameters $subEventQueryParameters);

    /**
     * @param CalendarType ...$calendarTypes
     * @return OfferQueryBuilderInterface
     */
    public function withCalendarTypeFilter(CalendarType ...$calendarTypes);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withPostalCodeFilter(PostalCode $postalCode);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withAddressCountryFilter(Country $country);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withRegionFilter(
        string $regionIndexName,
        string $regionDocumentType,
        RegionId $regionId
    );

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withGeoDistanceFilter(GeoDistanceParameters $geoDistance);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withGeoBoundsFilter(GeoBoundsParameters $geoBounds);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withAudienceTypeFilter(AudienceType $audienceType);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withAgeRangeFilter(Age $minimum = null, Age $maximum = null);

    /**
     * @param bool $include
     *   When set to true ONLY offers for all age ranges will be included.
     *   When set to false offers for all age ranges will be excluded.
     * @return OfferQueryBuilderInterface
     */
    public function withAllAgesFilter($include);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withPriceRangeFilter(Price $minimum = null, Price $maximum = null);

    /**
     * @param bool $include
     *   When set to true only offers with at least one media object will be
     *   included. When set to false offers with media objects will be excluded.
     * @return OfferQueryBuilderInterface
     */
    public function withMediaObjectsFilter($include);

    /**
     * @param bool $include
     *   When set to true only UiTPAS offers will be included. When set to
     *   false UiTPAS offers will be excluded.
     * @return OfferQueryBuilderInterface
     */
    public function withUiTPASFilter($include);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withTermIdFilter(TermId $termId);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withTermLabelFilter(TermLabel $termLabel);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withLocationTermIdFilter(TermId $locationTermId);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withLocationTermLabelFilter(TermLabel $locationTermLabel);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withLabelFilter(LabelName $label);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withLocationLabelFilter(LabelName $locationLabel);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withOrganizerLabelFilter(LabelName $organizerLabel);

    /**
     * @param bool $isDuplicate
     *   When set to true only offers marked as duplicate will be included.
     *   When set to false only canonical offers will be included.
     * @return OfferQueryBuilderInterface
     */
    public function withDuplicateFilter(bool $isDuplicate);

    /**
     * @return static
     */
    public function withProductionIdFilter(string $productionId);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withFacet(FacetName $facetName);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withSortByScore(SortOrder $sortOrder);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withSortByAvailableTo(SortOrder $sortOrder);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withSortByCreated(SortOrder $sortOrder);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withSortByModified(SortOrder $sortOrder);

    /**
     * @return OfferQueryBuilderInterface
     */
    public function withSortByDistance(Coordinates $coordinates, SortOrder $sortOrder);

    /**
     * @return static
     */
    public function withSortByPopularity(SortOrder $sortOrder);

    /**
     * @return static
     */
    public function withGroupByProductionId();
}
