<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Organizer;

use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\QueryBuilder;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;

interface OrganizerQueryBuilderInterface extends QueryBuilder
{
    public function withAutoCompleteFilter(string $input): OrganizerQueryBuilderInterface;

    public function withWebsiteFilter(string $url): OrganizerQueryBuilderInterface;

    public function withDomainFilter(string $domain): OrganizerQueryBuilderInterface;

    public function withPostalCodeFilter(PostalCode $postalCode): OrganizerQueryBuilderInterface;

    public function withAddressCountryFilter(Country $country): OrganizerQueryBuilderInterface;

    public function withRegionFilter(
        string $regionIndexName,
        string $regionDocumentType,
        RegionId $regionId
    ): OrganizerQueryBuilderInterface;

    public function withGeoDistanceFilter(GeoDistanceParameters $geoDistanceParameters): OrganizerQueryBuilderInterface;

    public function withGeoBoundsFilter(GeoBoundsParameters $geoBoundsParameters): OrganizerQueryBuilderInterface;

    public function withCreatorFilter(Creator $creator): OrganizerQueryBuilderInterface;

    public function withContributorsFilter(string $email): OrganizerQueryBuilderInterface;

    /**
     *  When set to true only organizers with at least one image will be included.
     *  When set to false organizers with images will be excluded.
     */
    public function withImagesFilter(bool $include): OrganizerQueryBuilderInterface;

    public function withLabelFilter(LabelName $label): OrganizerQueryBuilderInterface;

    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses): OrganizerQueryBuilderInterface;

    public function withFacet(FacetName $facetName): OrganizerQueryBuilderInterface;

    public function withSortByScore(SortOrder $sortOrder): OrganizerQueryBuilderInterface;

    public function withSortByCreated(SortOrder $sortOrder): OrganizerQueryBuilderInterface;

    public function withSortByModified(SortOrder $sortOrder): OrganizerQueryBuilderInterface;
}
