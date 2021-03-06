<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Organizer;

use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Url;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\QueryBuilder;
use CultuurNet\UDB3\Search\SortOrder;

interface OrganizerQueryBuilderInterface extends QueryBuilder
{
    /**
     * @return static
     */
    public function withAutoCompleteFilter(string $input);

    /**
     * @return static
     */
    public function withWebsiteFilter(Url $url);

    /**
     * @return static
     */
    public function withDomainFilter(string $domain);

    /**
     * @return static
     */
    public function withPostalCodeFilter(PostalCode $postalCode);

    /**
     * @return static
     */
    public function withAddressCountryFilter(Country $country);

    /**
     * @return static
     */
    public function withCreatorFilter(Creator $creator);

    /**
     * @return static
     */
    public function withLabelFilter(LabelName $label);

    /**
     * @param WorkflowStatus ...$workflowStatuses
     * @return static
     */
    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses);

    public function withSortByScore(SortOrder $sortOrder): OrganizerQueryBuilderInterface;

    public function withSortByCreated(SortOrder $sortOrder): OrganizerQueryBuilderInterface;

    public function withSortByModified(SortOrder $sortOrder): OrganizerQueryBuilderInterface;
}
