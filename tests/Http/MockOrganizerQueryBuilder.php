<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\AbstractQueryString;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Search\QueryBuilder;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\Start;
use ValueObjects\Geography\Country;
use ValueObjects\Web\Domain;
use ValueObjects\Web\Url;

final class MockOrganizerQueryBuilder implements OrganizerQueryBuilderInterface
{
    private $mockQuery = [];

    public function __construct()
    {
        $this->mockQuery['limit'] = 30;
        $this->mockQuery['start'] = 0;
    }

    public function withAutoCompleteFilter(string $input)
    {
        $c = clone $this;
        $c->mockQuery['autoComplete'] = (string) $input;
        return $c;
    }

    public function withWebsiteFilter(Url $url)
    {
        $c = clone $this;
        $c->mockQuery['website'] = (string) $url;
        return $c;
    }

    public function withDomainFilter(Domain $domain)
    {
        $c = clone $this;
        $c->mockQuery['domain'] = (string) $domain;
        return $c;
    }

    public function withPostalCodeFilter(PostalCode $postalCode)
    {
        $c = clone $this;
        $c->mockQuery['postalCode'] = $postalCode->toString();
        return $c;
    }

    public function withAddressCountryFilter(Country $country)
    {
        $c = clone $this;
        $c->mockQuery['country'] = (string) $country;
        return $c;
    }

    public function withCreatorFilter(Creator $creator)
    {
        $c = clone $this;
        $c->mockQuery['creator'] = $creator->toString();
        return $c;
    }

    public function withLabelFilter(LabelName $label)
    {
        $c = clone $this;
        $c->mockQuery['label'][] = $label->toString();
        return $c;
    }

    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses)
    {
        $c = clone $this;
        $c->mockQuery['workflowStatus'] = array_map(
            function (WorkflowStatus $workflowStatus) {
                return $workflowStatus->toString();
            },
            $workflowStatuses
        );
        return $c;
    }

    public function withAdvancedQuery(AbstractQueryString $queryString, Language ...$textLanguages)
    {
        $c = clone $this;
        $c->mockQuery['advancedQuery']['query'] = $queryString->toString();
        $c->mockQuery['advancedQuery']['language'] = array_map(
            function (Language $language) {
                return (string) $language;
            },
            $textLanguages
        );
        return $c;
    }

    public function withTextQuery(string $text, Language ...$textLanguages)
    {
        $c = clone $this;
        $c->mockQuery['textQuery']['query'] = $text;
        $c->mockQuery['textQuery']['language'] = array_map(
            function (Language $language) {
                return (string) $language;
            },
            $textLanguages
        );
        return $c;
    }

    public function withStart(Start $start)
    {
        $c = clone $this;
        $c->mockQuery['start'] = $start->toInteger();
        return $c;
    }

    public function withLimit(Limit $limit)
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

    public function withSortByScore(SortOrder $sortOrder): OrganizerQueryBuilderInterface
    {
        $c = clone $this;
        $c->mockQuery['sort']['score'] = $sortOrder->toNative();
        return $c;
    }

    public function withSortByCreated(SortOrder $sortOrder): OrganizerQueryBuilderInterface
    {
        $c = clone $this;
        $c->mockQuery['sort']['created'] = $sortOrder->toNative();
        return $c;
    }

    public function withSortByModified(SortOrder $sortOrder): OrganizerQueryBuilderInterface
    {
        $c = clone $this;
        $c->mockQuery['sort']['modified'] = $sortOrder->toNative();
        return $c;
    }

    public function build(): array
    {
        $build = $this->mockQuery;
        ksort($build);
        return $build;
    }
}
