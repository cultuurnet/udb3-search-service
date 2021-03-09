<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\KnownLanguages;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Search\SortOrder;
use Stringy\Stringy;
use ValueObjects\Geography\Country;
use ValueObjects\Web\Domain;
use ValueObjects\Web\Url;

final class ElasticSearchOrganizerQueryBuilder extends AbstractElasticSearchQueryBuilder implements
    OrganizerQueryBuilderInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->extraQueryParameters['_source'] = ['@id', '@type', 'originalEncodedJsonLd'];
    }

    protected function getPredefinedQueryStringFields(Language ...$languages): array
    {
        return [];
    }

    public function withAutoCompleteFilter(string $input)
    {
        // Currently not translatable, just look in the Dutch version for now.
        return $this->withMatchPhraseQuery('name.nl.autocomplete', $input);
    }

    public function withWebsiteFilter(Url $url)
    {
        return $this->withMatchQuery('url', (string) $url);
    }

    public function withDomainFilter(Domain $domain)
    {
        $domain = Stringy::create((string) $domain);
        $domain = $domain->removeLeft('www.');

        return $this->withTermQuery('domain', (string) $domain);
    }

    public function withPostalCodeFilter(PostalCode $postalCode)
    {
        return $this->withMultiFieldMatchQuery(
            (new KnownLanguages())->fieldNames(
                'address.{{lang}}.postalCode'
            ),
            $postalCode->toString()
        );
    }

    public function withAddressCountryFilter(Country $country)
    {
        return $this->withMultiFieldMatchQuery(
            (new KnownLanguages())->fieldNames(
                'address.{{lang}}.addressCountry'
            ),
            $country->getCode()->toNative()
        );
    }

    public function withCreatorFilter(Creator $creator)
    {
        return $this->withMatchQuery('creator', $creator->toString());
    }

    public function withLabelFilter(LabelName $label)
    {
        return $this->withMatchQuery('labels', $label->toString());
    }

    public function withWorkflowStatusFilter(WorkflowStatus ...$workflowStatuses): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withMultiValueMatchQuery(
            'workflowStatus',
            array_map(
                function (WorkflowStatus $workflowStatus) {
                    return $workflowStatus->toString();
                },
                $workflowStatuses
            )
        );
    }

    public function withSortByScore(SortOrder $sortOrder): OrganizerQueryBuilderInterface
    {
        return $this->withFieldSort('_score', $sortOrder->toNative());
    }

    public function withSortByCreated(SortOrder $sortOrder): OrganizerQueryBuilderInterface
    {
        return $this->withFieldSort('created', $sortOrder->toNative());
    }

    public function withSortByModified(SortOrder $sortOrder): OrganizerQueryBuilderInterface
    {
        return $this->withFieldSort('modified', $sortOrder->toNative());
    }
}
