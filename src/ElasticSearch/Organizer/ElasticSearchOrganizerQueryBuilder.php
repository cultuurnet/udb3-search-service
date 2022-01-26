<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Url;
use CultuurNet\UDB3\Search\ElasticSearch\KnownLanguages;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Search\SortOrder;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceQuery;

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

    public function withAutoCompleteFilter(string $input): ElasticSearchOrganizerQueryBuilder
    {
        // Currently not translatable, just look in the Dutch version for now.
        return $this->withMatchPhraseQuery('name.nl.autocomplete', $input);
    }

    public function withWebsiteFilter(Url $url): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withMatchQuery('url', $url->getNormalizedUrl());
    }

    public function withDomainFilter(string $domain): ElasticSearchOrganizerQueryBuilder
    {
        if (strpos($domain, 'www.') === 0) {
            $domain = substr($domain, strlen('www.'));
        }
        return $this->withTermQuery('domain', $domain);
    }

    public function withPostalCodeFilter(PostalCode $postalCode): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withMultiFieldMatchQuery(
            (new KnownLanguages())->fieldNames(
                'address.{{lang}}.postalCode'
            ),
            $postalCode->toString()
        );
    }

    public function withAddressCountryFilter(Country $country): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withMultiFieldMatchQuery(
            (new KnownLanguages())->fieldNames(
                'address.{{lang}}.addressCountry'
            ),
            $country->toString()
        );
    }

    public function withGeoDistanceFilter(GeoDistanceParameters $geoDistanceParameters): self
    {
        $geoDistanceQuery = new GeoDistanceQuery(
            'geo_point',
            $geoDistanceParameters->getMaximumDistance()->toString(),
            (object) [
                'lat' => $geoDistanceParameters->getCoordinates()->getLatitude()->toDouble(),
                'lon' => $geoDistanceParameters->getCoordinates()->getLongitude()->toDouble(),
            ]
        );

        $c = $this->getClone();
        $c->boolQuery->add($geoDistanceQuery, BoolQuery::FILTER);
        return $c;
    }

    public function withCreatorFilter(Creator $creator): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withMatchQuery('creator', $creator->toString());
    }

    public function withLabelFilter(LabelName $label): ElasticSearchOrganizerQueryBuilder
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

    public function withSortByScore(SortOrder $sortOrder): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withFieldSort('_score', $sortOrder->toString());
    }

    public function withSortByCreated(SortOrder $sortOrder): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withFieldSort('created', $sortOrder->toString());
    }

    public function withSortByModified(SortOrder $sortOrder): ElasticSearchOrganizerQueryBuilder
    {
        return $this->withFieldSort('modified', $sortOrder->toString());
    }
}
