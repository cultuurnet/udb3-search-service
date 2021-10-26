<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\AbstractQueryString;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Limit;
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
use CultuurNet\UDB3\Search\Offer\WorkflowStatus;
use CultuurNet\UDB3\Search\PriceInfo\Price;
use CultuurNet\UDB3\Search\QueryBuilder;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\Start;
use DateTimeImmutable;

final class MockOfferQueryBuilder implements OfferQueryBuilderInterface
{
    private $mockQuery = [];

    public function __construct()
    {
        $this->mockQuery['limit'] = 30;
        $this->mockQuery['start'] = 0;
    }

    public function withCdbIdFilter(Cdbid $cdbid): self
    {
        $c = clone $this;
        $c->mockQuery['cdbId'] = $cdbid->toString();
        return $c;
    }

    public function withLocationCdbIdFilter(Cdbid $locationCdbid): self
    {
        $c = clone $this;
        $c->mockQuery['locationCdbId'] = $locationCdbid->toString();
        return $c;
    }

    public function withOrganizerCdbIdFilter(Cdbid $organizerCdbId): self
    {
        $c = clone $this;
        $c->mockQuery['organizerCdbId'] = $organizerCdbId->toString();
        return $c;
    }

    public function withMainLanguageFilter(Language $mainLanguage): self
    {
        $c = clone $this;
        $c->mockQuery['mainLanguage'] = (string) $mainLanguage;
        return $c;
    }

    public function withLanguageFilter(Language $language): self
    {
        $c = clone $this;
        $c->mockQuery['language'][] = (string) $language;
        return $c;
    }

    public function withCompletedLanguageFilter(Language $language): self
    {
        $c = clone $this;
        $c->mockQuery['completedLanguage'][] = (string) $language;
        return $c;
    }

    public function withAvailableRangeFilter(DateTimeImmutable $from = null, DateTimeImmutable $to = null): self
    {
        $c = clone $this;
        $c->mockQuery['availableRange']['from'] = $from ? $from->format(DATE_ATOM) : null;
        $c->mockQuery['availableRange']['to'] = $to ? $to->format(DATE_ATOM) : null;
        return $c;
    }

    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses): self
    {
        if (empty($workflowStatuses)) {
            return $this;
        }

        $c = clone $this;
        $c->mockQuery['workflowStatus'] = array_map(
            static function (WorkflowStatus $workflowStatus) {
                return $workflowStatus->toString();
            },
            $workflowStatuses
        );
        return $c;
    }

    public function withCreatedRangeFilter(DateTimeImmutable $from = null, DateTimeImmutable $to = null): self
    {
        $c = clone $this;
        $c->mockQuery['createdRange']['from'] = $from ? $from->format(DATE_ATOM) : null;
        $c->mockQuery['createdRange']['to'] = $to ? $to->format(DATE_ATOM) : null;
        return $c;
    }

    public function withModifiedRangeFilter(DateTimeImmutable $from = null, DateTimeImmutable $to = null): self
    {
        $c = clone $this;
        $c->mockQuery['modifiedRange']['from'] = $from ? $from->format(DATE_ATOM) : null;
        $c->mockQuery['modifiedRange']['to'] = $to ? $to->format(DATE_ATOM) : null;
        return $c;
    }

    public function withCreatorFilter(Creator $creator): self
    {
        $c = clone $this;
        $c->mockQuery['creator'] = $creator->toString();
        return $c;
    }

    public function withDateRangeFilter(DateTimeImmutable $from = null, DateTimeImmutable $to = null): self
    {
        $c = clone $this;
        $c->mockQuery['dateRange']['from'] = $from ? $from->format(DATE_ATOM) : null;
        $c->mockQuery['dateRange']['to'] = $to ? $to->format(DATE_ATOM) : null;
        return $c;
    }

    public function withLocalTimeRangeFilter(int $localTimeFrom = null, int $localTimeTo = null): self
    {
        $c = clone $this;
        $c->mockQuery['localTimeRange']['from'] = $localTimeFrom;
        $c->mockQuery['localTimeRange']['to'] = $localTimeTo;
        return $c;
    }

    public function withStatusFilter(Status ...$statuses): self
    {
        if (empty($statuses)) {
            return $this;
        }

        $c = clone $this;
        $c->mockQuery['status'] = array_map(
            static function (Status $status) {
                return $status->toString();
            },
            $statuses
        );
        return $c;
    }

    public function withBookingAvailabilityFilter(string $bookingAvailability): self
    {
        $c = clone $this;
        $c->mockQuery['bookingAvailability'] = $bookingAvailability;
        return $c;
    }

    public function withSubEventFilter(SubEventQueryParameters $subEventQueryParameters): self
    {
        $c = clone $this;

        $dateFrom = $subEventQueryParameters->getDateFrom();
        $dateTo = $subEventQueryParameters->getDateTo();

        $c->mockQuery['subEvent'] = [
            'dateFrom' => $dateFrom ? $dateFrom->format(DATE_ATOM) : null,
            'dateTo' => $dateTo ? $dateTo->format(DATE_ATOM) : null,
            'statuses' => array_map(
                static function (Status $status) {
                    return $status->toString();
                },
                $subEventQueryParameters->getStatuses()
            ),
        ];

        return $c;
    }

    public function withCalendarTypeFilter(CalendarType ...$calendarTypes): self
    {
        $c = clone $this;
        $c->mockQuery['calendarType'] = array_map(
            static function (CalendarType $calendarType) {
                return $calendarType->toString();
            },
            $calendarTypes
        );
        return $c;
    }

    public function withPostalCodeFilter(PostalCode $postalCode): self
    {
        $c = clone $this;
        $c->mockQuery['postalCode'] = $postalCode->toString();
        return $c;
    }

    public function withAddressCountryFilter(Country $country): self
    {
        $c = clone $this;
        $c->mockQuery['country'] = $country->toString();
        return $c;
    }

    public function withRegionFilter(
        string $regionIndexName,
        string $regionDocumentType,
        RegionId $regionId
    ): self {
        $c = clone $this;
        $c->mockQuery['region']['index'] = $regionIndexName;
        $c->mockQuery['region']['type'] = $regionDocumentType;
        $c->mockQuery['region']['id'] = $regionId->toString();
        return $c;
    }

    public function withGeoDistanceFilter(GeoDistanceParameters $geoDistanceParameters): self
    {
        $c = clone $this;
        $c->mockQuery['geoDistance']['lat'] = $geoDistanceParameters->getCoordinates()->getLatitude()->toDouble();
        $c->mockQuery['geoDistance']['lng'] = $geoDistanceParameters->getCoordinates()->getLongitude()->toDouble();
        $c->mockQuery['geoDistance']['distance'] = $geoDistanceParameters->getMaximumDistance()->toString();
        return $c;
    }

    public function withGeoBoundsFilter(GeoBoundsParameters $geoBoundsParameters): self
    {
        $c = clone $this;

        $c->mockQuery['geoBounds']['northWest']['lat'] = $geoBoundsParameters->getNorthWestCoordinates()
            ->getLatitude()->toDouble();
        $c->mockQuery['geoBounds']['northWest']['lng'] = $geoBoundsParameters->getNorthWestCoordinates()
            ->getLongitude()->toDouble();

        $c->mockQuery['geoBounds']['northEast']['lat'] = $geoBoundsParameters->getNorthEastCoordinates()
            ->getLatitude()->toDouble();
        $c->mockQuery['geoBounds']['northEast']['lng'] = $geoBoundsParameters->getNorthEastCoordinates()
            ->getLongitude()->toDouble();

        $c->mockQuery['geoBounds']['southWest']['lat'] = $geoBoundsParameters->getSouthWestCoordinates()
            ->getLatitude()->toDouble();
        $c->mockQuery['geoBounds']['southWest']['lng'] = $geoBoundsParameters->getSouthWestCoordinates()
            ->getLongitude()->toDouble();

        $c->mockQuery['geoBounds']['southEast']['lat'] = $geoBoundsParameters->getSouthEastCoordinates()
            ->getLatitude()->toDouble();
        $c->mockQuery['geoBounds']['southEast']['lng'] = $geoBoundsParameters->getSouthEastCoordinates()
            ->getLongitude()->toDouble();

        return $c;
    }

    public function withAudienceTypeFilter(AudienceType $audienceType): self
    {
        $c = clone $this;
        $c->mockQuery['audienceType'] = $audienceType->toString();
        return $c;
    }

    public function withAgeRangeFilter(Age $minimum = null, Age $maximum = null): self
    {
        $c = clone $this;
        $c->mockQuery['ageRange']['from'] = $minimum ? $minimum->toNative() : null;
        $c->mockQuery['ageRange']['to'] = $maximum ? $maximum->toNative() : null;
        return $c;
    }

    public function withAllAgesFilter($include): self
    {
        $c = clone $this;
        $c->mockQuery['allAgesOnly'] = (bool) $include;
        return $c;
    }

    public function withPriceRangeFilter(Price $minimum = null, Price $maximum = null): self
    {
        $c = clone $this;
        $c->mockQuery['priceRange']['from'] = $minimum ? $minimum->toNative() : null;
        $c->mockQuery['priceRange']['to'] = $maximum ? $maximum->toNative() : null;
        return $c;
    }

    public function withMediaObjectsFilter($include): self
    {
        $c = clone $this;
        $c->mockQuery['mediaObjects'] = (bool) $include;
        return $c;
    }

    public function withVideosFilter(bool $include): self
    {
        $c = clone $this;
        $c->mockQuery['videos'] = (bool) $include;
        return $c;
    }

    public function withUiTPASFilter($include): self
    {
        $c = clone $this;
        $c->mockQuery['uitpas'] = (bool) $include;
        return $c;
    }

    public function withTermIdFilter(TermId $termId): self
    {
        $c = clone $this;
        $c->mockQuery['termId'][] = $termId->toString();
        return $c;
    }

    public function withTermLabelFilter(TermLabel $termLabel): self
    {
        $c = clone $this;
        $c->mockQuery['termLabel'][] = $termLabel->toString();
        return $c;
    }

    public function withLocationTermIdFilter(TermId $locationTermId): self
    {
        $c = clone $this;
        $c->mockQuery['locationTermId'][] = $locationTermId->toString();
        return $c;
    }

    public function withLocationTermLabelFilter(TermLabel $locationTermLabel): self
    {
        $c = clone $this;
        $c->mockQuery['locationTermLabel'][] = $locationTermLabel->toString();
        return $c;
    }

    public function withLabelFilter(LabelName $label): self
    {
        $c = clone $this;
        $c->mockQuery['label'][] = $label->toString();
        return $c;
    }

    public function withLocationLabelFilter(LabelName $locationLabel): self
    {
        $c = clone $this;
        $c->mockQuery['locationLabel'][] = $locationLabel->toString();
        return $c;
    }

    public function withOrganizerLabelFilter(LabelName $organizerLabel): self
    {
        $c = clone $this;
        $c->mockQuery['organizerLabel'][] = $organizerLabel->toString();
        return $c;
    }

    public function withDuplicateFilter(bool $isDuplicate): self
    {
        $c = clone $this;
        $c->mockQuery['isDuplicate'] = $isDuplicate;
        return $c;
    }

    public function withProductionIdFilter(string $productionId): self
    {
        $c = clone $this;
        $c->mockQuery['productionId'] = $productionId;
        return $c;
    }

    public function withFacet(FacetName $facetName): self
    {
        $c = clone $this;
        $c->mockQuery['facet'][] = $facetName->toString();
        return $c;
    }

    public function withSortByScore(SortOrder $sortOrder): self
    {
        $c = clone $this;
        $c->mockQuery['sort']['score'] = $sortOrder->toString();
        return $c;
    }

    public function withSortByAvailableTo(SortOrder $sortOrder): self
    {
        $c = clone $this;
        $c->mockQuery['sort']['availableTo'] = $sortOrder->toString();
        return $c;
    }

    public function withSortByCreated(SortOrder $sortOrder): self
    {
        $c = clone $this;
        $c->mockQuery['sort']['created'] = $sortOrder->toString();
        return $c;
    }

    public function withSortByModified(SortOrder $sortOrder): self
    {
        $c = clone $this;
        $c->mockQuery['sort']['modified'] = $sortOrder->toString();
        return $c;
    }

    public function withSortByDistance(Coordinates $coordinates, SortOrder $sortOrder): self
    {
        $c = clone $this;
        $c->mockQuery['sort']['distance']['lat'] = $coordinates->getLatitude()->toDouble();
        $c->mockQuery['sort']['distance']['lng'] = $coordinates->getLongitude()->toDouble();
        $c->mockQuery['sort']['distance']['order'] = $sortOrder->toString();
        return $c;
    }

    public function withSortByPopularity(SortOrder $sortOrder): self
    {
        $c = clone $this;
        $c->mockQuery['sort']['popularity'] = $sortOrder->toString();
        return $c;
    }

    public function withGroupByProductionId(): self
    {
        $c = clone $this;
        $c->mockQuery['group'][] = 'productionId';
        return $c;
    }

    public function withAdvancedQuery(AbstractQueryString $queryString, Language ...$textLanguages): self
    {
        $c = clone $this;
        $c->mockQuery['advancedQuery']['query'] = $queryString->toString();
        $c->mockQuery['advancedQuery']['language'] = array_map(
            static function (Language $language) {
                return (string) $language;
            },
            $textLanguages
        );
        return $c;
    }

    public function withTextQuery(string $text, Language ...$textLanguages): self
    {
        $c = clone $this;
        $c->mockQuery['textQuery']['query'] = $text;
        $c->mockQuery['textQuery']['language'] = array_map(
            static function (Language $language) {
                return (string) $language;
            },
            $textLanguages
        );
        return $c;
    }

    public function withStart(Start $start): self
    {
        $c = clone $this;
        $c->mockQuery['start'] = $start->toInteger();
        return $c;
    }

    public function withLimit(Limit $limit): self
    {
        $c = clone $this;
        $c->mockQuery['limit'] = $limit->toInteger();
        return $c;
    }

    public function getLimit(): Limit
    {
        if (!isset($this->mockQuery['limit'])) {
            return new Limit(QueryBuilder::DEFAULT_LIMIT);
        }

        return new Limit($this->mockQuery['limit']);
    }

    public function build(): array
    {
        $build = $this->mockQuery;
        ksort($build);
        return $build;
    }
}
