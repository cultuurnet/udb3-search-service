<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\AbstractQueryString;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Search\QueryBuilder;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\Start;

final class MockOrganizerQueryBuilder implements OrganizerQueryBuilderInterface
{
    private $mockQuery = [];

    public function __construct()
    {
        $this->mockQuery['limit'] = 30;
        $this->mockQuery['start'] = 0;
    }

    public function withIdFilter(string $organizerId): self
    {
        $c = clone $this;
        $c->mockQuery['organizerId'] = $organizerId;
        return $c;
    }

    public function withAutoCompleteFilter(string $input): MockOrganizerQueryBuilder
    {
        $c = clone $this;
        $c->mockQuery['autoComplete'] = $input;
        return $c;
    }

    public function withWebsiteFilter(string $url): MockOrganizerQueryBuilder
    {
        $c = clone $this;
        $c->mockQuery['website'] = $url;
        return $c;
    }

    public function withDomainFilter(string $domain): MockOrganizerQueryBuilder
    {
        $c = clone $this;
        $c->mockQuery['domain'] = $domain;
        return $c;
    }

    public function withPostalCodeFilter(PostalCode $postalCode): MockOrganizerQueryBuilder
    {
        $c = clone $this;
        $c->mockQuery['postalCode'] = $postalCode->toString();
        return $c;
    }

    public function withAddressCountryFilter(Country $country): MockOrganizerQueryBuilder
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

    public function withCreatorFilter(Creator $creator): MockOrganizerQueryBuilder
    {
        $c = clone $this;
        $c->mockQuery['creator'] = $creator->toString();
        return $c;
    }

    public function withImagesFilter(bool $include): MockOrganizerQueryBuilder
    {
        $c = clone $this;
        $c->mockQuery['images'] = $include;
        return $c;
    }

    public function withLabelFilter(LabelName $label): MockOrganizerQueryBuilder
    {
        $c = clone $this;
        $c->mockQuery['label'][] = $label->toString();
        return $c;
    }

    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses): MockOrganizerQueryBuilder
    {
        $c = clone $this;
        $c->mockQuery['workflowStatus'] = array_map(
            fn (WorkflowStatus $workflowStatus): string => $workflowStatus->toString(),
            $workflowStatuses
        );
        return $c;
    }

    public function withContributorsFilter(string $email): OrganizerQueryBuilderInterface
    {
        $c = clone $this;
        $c->mockQuery['contributors'] = $email;
        return $c;
    }

    public function withFacet(FacetName $facetName): self
    {
        $c = clone $this;
        $c->mockQuery['facet'][] = $facetName->toString();
        return $c;
    }

    public function withAdvancedQuery(AbstractQueryString $queryString, Language ...$textLanguages): MockOrganizerQueryBuilder
    {
        $c = clone $this;
        $c->mockQuery['advancedQuery']['query'] = $queryString->toString();
        $c->mockQuery['advancedQuery']['language'] = array_map(
            fn (Language $language): string => (string) $language,
            $textLanguages
        );
        return $c;
    }

    public function withTextQuery(string $text, Language ...$textLanguages): MockOrganizerQueryBuilder
    {
        $c = clone $this;
        $c->mockQuery['textQuery']['query'] = $text;
        $c->mockQuery['textQuery']['language'] = array_map(
            fn (Language $language): string => (string) $language,
            $textLanguages
        );
        return $c;
    }

    public function withStartAndLimit(Start $start, Limit $limit): MockOrganizerQueryBuilder
    {
        $c = clone $this;
        $c->mockQuery['start'] = $start->toInteger();
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

    public function withSortByScore(SortOrder $sortOrder): OrganizerQueryBuilderInterface
    {
        $c = clone $this;
        $c->mockQuery['sort']['score'] = $sortOrder->toString();
        return $c;
    }

    public function withSortByCompleteness(SortOrder $sortOrder): OrganizerQueryBuilderInterface
    {
        $c = clone $this;
        $c->mockQuery['sort']['completeness'] = $sortOrder->toString();
        return $c;
    }

    public function withSortByCreated(SortOrder $sortOrder): OrganizerQueryBuilderInterface
    {
        $c = clone $this;
        $c->mockQuery['sort']['created'] = $sortOrder->toString();
        return $c;
    }

    public function withSortByModified(SortOrder $sortOrder): OrganizerQueryBuilderInterface
    {
        $c = clone $this;
        $c->mockQuery['sort']['modified'] = $sortOrder->toString();
        return $c;
    }

    public function build(): array
    {
        $build = $this->mockQuery;
        ksort($build);
        return $build;
    }
}
