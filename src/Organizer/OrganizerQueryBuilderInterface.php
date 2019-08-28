<?php

namespace CultuurNet\UDB3\Search\Organizer;

use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\QueryBuilderInterface;
use ValueObjects\Geography\Country;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Domain;
use ValueObjects\Web\Url;

interface OrganizerQueryBuilderInterface extends QueryBuilderInterface
{
    /**
     * @param StringLiteral $input
     * @return static
     */
    public function withAutoCompleteFilter(StringLiteral $input);

    /**
     * @param Url $url
     * @return static
     */
    public function withWebsiteFilter(Url $url);

    /**
     * @param Domain $domain
     * @return static
     */
    public function withDomainFilter(Domain $domain);

    /**
     * @param PostalCode $postalCode
     * @return static
     */
    public function withPostalCodeFilter(PostalCode $postalCode);

    /**
     * @param Country $country
     * @return static
     */
    public function withAddressCountryFilter(Country $country);

    /**
     * @param Creator $creator
     * @return static
     */
    public function withCreatorFilter(Creator $creator);

    /**
     * @param LabelName $label
     * @return static
     */
    public function withLabelFilter(LabelName $label);

    /**
     * @param WorkflowStatus[] $workflowStatuses
     * @return static
     */
    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses);
}
