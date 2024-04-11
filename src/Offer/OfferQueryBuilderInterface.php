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
use DateTimeImmutable;

/**
 * Multiple filters are combined using AND.
 * Filters that accept multiple values use OR internally.
 */
interface OfferQueryBuilderInterface extends QueryBuilder
{
    public function withCdbIdFilter(Cdbid $cdbid): OfferQueryBuilderInterface;

    public function withLocationCdbIdFilter(Cdbid $locationCdbid): OfferQueryBuilderInterface;

    public function withOrganizerCdbIdFilter(Cdbid $organizerCdbId): OfferQueryBuilderInterface;

    public function withMainLanguageFilter(Language $mainLanguage): OfferQueryBuilderInterface;

    public function withLanguageFilter(Language $language): OfferQueryBuilderInterface;

    public function withCompletedLanguageFilter(Language $language): OfferQueryBuilderInterface;

    public function withAvailableRangeFilter(
        DateTimeImmutable $from = null,
        DateTimeImmutable $to = null
    ): OfferQueryBuilderInterface;

    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses): OfferQueryBuilderInterface;

    public function withCreatedRangeFilter(
        DateTimeImmutable $from = null,
        DateTimeImmutable $to = null
    ): OfferQueryBuilderInterface;

    public function withModifiedRangeFilter(
        DateTimeImmutable $from = null,
        DateTimeImmutable $to = null
    ): OfferQueryBuilderInterface;

    public function withCreatorFilter(Creator $creator): OfferQueryBuilderInterface;

    public function withDateRangeFilter(
        DateTimeImmutable $from = null,
        DateTimeImmutable $to = null
    ): OfferQueryBuilderInterface;

    public function withLocalTimeRangeFilter(
        int $localTimeFrom = null,
        int $localTimeTo = null
    ): OfferQueryBuilderInterface;

    public function withStatusFilter(Status ...$statuses): OfferQueryBuilderInterface;

    public function withAttendanceModeFilter(AttendanceMode ...$attendanceModes): OfferQueryBuilderInterface;

    public function withBookingAvailabilityFilter(string $bookingAvailability): OfferQueryBuilderInterface;

    public function withSubEventFilter(SubEventQueryParameters $subEventQueryParameters): OfferQueryBuilderInterface;

    public function withCalendarTypeFilter(CalendarType ...$calendarTypes): OfferQueryBuilderInterface;

    public function withPostalCodeFilter(PostalCode $postalCode): OfferQueryBuilderInterface;

    public function withAddressCountryFilter(Country $country): OfferQueryBuilderInterface;

    public function withRegionFilter(
        string $regionIndexName,
        string $regionDocumentType,
        RegionId $regionId
    ): OfferQueryBuilderInterface;

    public function withGeoDistanceFilter(GeoDistanceParameters $geoDistanceParameters): OfferQueryBuilderInterface;

    public function withGeoBoundsFilter(GeoBoundsParameters $geoBoundsParameters): OfferQueryBuilderInterface;

    public function withAudienceTypeFilter(AudienceType $audienceType): OfferQueryBuilderInterface;

    public function withAgeRangeFilter(Age $minimum = null, Age $maximum = null): OfferQueryBuilderInterface;

    /**
     *   When set to true ONLY offers for all age ranges will be included.
     *   When set to false offers for all age ranges will be excluded.
     */
    public function withAllAgesFilter(bool $include): OfferQueryBuilderInterface;

    public function withPriceRangeFilter(Price $minimum = null, Price $maximum = null): OfferQueryBuilderInterface;

    /**
     *   When set to true only offers with at least one media object will be
     *   included. When set to false offers with media objects will be excluded.
     */
    public function withMediaObjectsFilter(bool $include): OfferQueryBuilderInterface;

    /**
     *   When set to true only offers with at least one video will be
     *   included. When set to false offers with videos will be excluded.
     */
    public function withVideosFilter(bool $include): OfferQueryBuilderInterface;

    /**
     *   When set to true only UiTPAS offers will be included. When set to
     *   false UiTPAS offers will be excluded.
     */
    public function withUiTPASFilter(bool $include): OfferQueryBuilderInterface;

    public function withTermIdFilter(TermId $termId): OfferQueryBuilderInterface;

    public function withTermLabelFilter(TermLabel $termLabel): OfferQueryBuilderInterface;

    public function withLocationTermIdFilter(TermId $locationTermId): OfferQueryBuilderInterface;

    public function withLocationTermLabelFilter(TermLabel $locationTermLabel): OfferQueryBuilderInterface;

    public function withLabelFilter(LabelName $label): OfferQueryBuilderInterface;

    public function withLocationLabelFilter(LabelName $locationLabel): OfferQueryBuilderInterface;

    public function withOrganizerLabelFilter(LabelName $organizerLabel): OfferQueryBuilderInterface;

    public function withContributorsFilter(string $email): OfferQueryBuilderInterface;

    /**
     *   When set to true only offers marked as duplicate will be included.
     *   When set to false only canonical offers will be included.
     */
    public function withDuplicateFilter(bool $isDuplicate): OfferQueryBuilderInterface;

    public function withProductionIdFilter(string $productionId): OfferQueryBuilderInterface;

    public function withRecommendationForFilter(string $eventId): OfferQueryBuilderInterface;

    public function withFacet(FacetName $facetName): OfferQueryBuilderInterface;

    public function withSortByScore(SortOrder $sortOrder): OfferQueryBuilderInterface;

    public function withSortByCompleteness(SortOrder $sortOrder): OfferQueryBuilderInterface;

    public function withSortByAvailableTo(SortOrder $sortOrder): OfferQueryBuilderInterface;

    public function withSortByCreated(SortOrder $sortOrder): OfferQueryBuilderInterface;

    public function withSortByModified(SortOrder $sortOrder): OfferQueryBuilderInterface;

    public function withSortByDistance(Coordinates $coordinates, SortOrder $sortOrder): OfferQueryBuilderInterface;

    public function withSortByPopularity(SortOrder $sortOrder): OfferQueryBuilderInterface;

    public function withSortByRecommendationScore(string $recommendationFor, SortOrder $sortOrder): OfferQueryBuilderInterface;

    public function withGroupByProductionId(): OfferQueryBuilderInterface;

    public function withSortBuilders(array $sorts, array $sortBuilders): OfferQueryBuilderInterface;
}
