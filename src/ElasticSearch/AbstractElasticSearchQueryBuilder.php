<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\AbstractQueryString;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\QueryBuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhraseQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractElasticSearchQueryBuilder implements QueryBuilderInterface
{
    /**
     * @var Search
     */
    protected $search;

    /**
     * @var BoolQuery
     */
    protected $boolQuery;

    /**
     * @var ?string
     */
    private $shardPreference;

    /**
     * @var array
     */
    protected $extraQueryParameters = [];

    public function __construct()
    {
        $this->boolQuery = new BoolQuery();
        $this->boolQuery->add(new MatchAllQuery(), BoolQuery::MUST);

        $this->search = new Search();
        $this->search->addQuery($this->boolQuery);

        $this->search->setFrom(0);
        $this->search->setSize(30);
    }

    public function withAdvancedQuery(AbstractQueryString $queryString, Language ...$textLanguages)
    {
        if (empty($textLanguages)) {
            $textLanguages = $this->getDefaultLanguages();
        }

        return $this->withQueryStringQuery(
            $queryString->toNative(),
            $this->getPredefinedQueryStringFields(...$textLanguages)
        );
    }

    public function withTextQuery(StringLiteral $text, Language ...$textLanguages)
    {
        if (empty($textLanguages)) {
            $textLanguages = $this->getDefaultLanguages();
        }

        return $this->withQueryStringQuery(
            str_replace(':', '\\:', $text->toNative()),
            $this->getPredefinedQueryStringFields(...$textLanguages),
            BoolQuery::MUST,
            'AND'
        );
    }

    public function withStart(Natural $start)
    {
        $c = $this->getClone();
        $c->search->setFrom($start->toNative());
        return $c;
    }

    public function withLimit(Natural $limit)
    {
        $c = $this->getClone();
        $c->search->setSize($limit->toNative());
        return $c;
    }

    public function getLimit(): Natural
    {
        $size = $this->search->getSize();
        return $size ? new Natural($size) : new Natural(QueryBuilderInterface::DEFAULT_LIMIT);
    }

    public function build(): array
    {
        return array_merge(
            $this->search->toArray(),
            $this->extraQueryParameters
        );
    }

    /**
     * @param Language ...$languages
     * @return string[]
     */
    abstract protected function getPredefinedQueryStringFields(Language ...$languages): array;

    /**
     * @return static
     */
    protected function getClone()
    {
        // @see http://stackoverflow.com/questions/10831798/php-deep-clone-object
        // We need to do a deep clone so the DSL objects don't get mutated by
        // accident. If we simply use the clone keyword all properties are
        // still references to the original objects. Note that myclabs/deep-copy
        // is too slow when applying a lot of filters.
        return unserialize(serialize($this));
    }

    /**
     * @param string $parameterName
     * @param Natural|null $min
     * @param Natural|null $max
     * @throws \InvalidArgumentException
     */
    protected function guardNaturalIntegerRange(
        $parameterName,
        Natural $min = null,
        Natural $max = null
    ) {
        if (!is_null($min) && !is_null($max) && $min->toInteger() > $max->toInteger()) {
             throw new \InvalidArgumentException(
                 "Minimum {$parameterName} should be smaller or equal to maximum {$parameterName}."
             );
        }
    }

    /**
     * @param string $parameterName
     * @param \DateTimeImmutable|null $from
     * @param \DateTimeImmutable|null $to
     * @throws \InvalidArgumentException
     */
    protected function guardDateRange(
        $parameterName,
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ) {
        if (!is_null($from) && !is_null($to) && $from > $to) {
            throw new \InvalidArgumentException(
                "Start {$parameterName} date should be equal to or smaller than end {$parameterName} date."
            );
        }
    }

    /**
     * @param string $fieldName
     * @param string $term
     * @return static
     */
    protected function withMatchQuery($fieldName, $term)
    {
        $matchQuery = new MatchQuery($fieldName, $term);

        $c = $this->getClone();
        $c->boolQuery->add($matchQuery, BoolQuery::FILTER);
        return $c;
    }

    /**
     * @param string $fieldName
     * @param string $term
     * @return static
     */
    protected function withTermQuery($fieldName, $term)
    {
        $termQuery = new TermQuery($fieldName, $term);

        $c = $this->getClone();
        $c->boolQuery->add($termQuery, BoolQuery::FILTER);
        return $c;
    }

    /**
     * @param string $fieldName
     * @param string[] $terms
     * @return static
     */
    protected function withMultiValueMatchQuery($fieldName, array $terms)
    {
        if (empty($terms)) {
            return $this;
        }

        if (count($terms) == 1) {
            return $this->withMatchQuery($fieldName, $terms[0]);
        }

        $nestedBoolQuery = new BoolQuery();

        foreach ($terms as $term) {
            $matchQuery = new MatchQuery($fieldName, $term);
            $nestedBoolQuery->add($matchQuery, BoolQuery::SHOULD);
        }

        $c = $this->getClone();
        $c->boolQuery->add($nestedBoolQuery, BoolQuery::FILTER);
        return $c;
    }

    /**
     * @param string[] $fieldNames
     * @param string $term
     * @return static
     */
    protected function withMultiFieldMatchQuery(array $fieldNames, $term)
    {
        $nestedBoolQuery = new BoolQuery();

        foreach ($fieldNames as $fieldName) {
            $matchQuery = new MatchQuery($fieldName, $term);
            $nestedBoolQuery->add($matchQuery, BoolQuery::SHOULD);
        }

        $c = $this->getClone();
        $c->boolQuery->add($nestedBoolQuery, BoolQuery::FILTER);
        return $c;
    }

    /**
     * @param string $fieldName
     * @param string $term
     * @return static
     */
    protected function withMatchPhraseQuery($fieldName, $term)
    {
        $matchPhraseQuery = new MatchPhraseQuery($fieldName, $term);

        $c = $this->getClone();
        $c->boolQuery->add($matchPhraseQuery, BoolQuery::FILTER);
        $c->boolQuery->add($matchPhraseQuery, BoolQuery::SHOULD);
        return $c;
    }

    /**
     * @param string $fieldName
     * @param string|int|float|null $from
     * @param string|int|float|null $to
     * @return static
     */
    protected function withRangeQuery($fieldName, $from = null, $to = null)
    {
        $rangeQuery = $this->createRangeQuery($fieldName, $from, $to);
        if (!$rangeQuery) {
            return $this;
        }

        $c = $this->getClone();
        $c->boolQuery->add($rangeQuery, BoolQuery::FILTER);
        return $c;
    }

    /**
     * @param string[] $fieldNames
     * @param string|int|float|null $from
     * @param string|int|float|null $to
     * @return static
     */
    protected function withMultiFieldRangeQuery(array $fieldNames, $from = null, $to = null)
    {
        $nestedBoolQuery = new BoolQuery();

        foreach ($fieldNames as $fieldName) {
            $rangeQuery = $this->createRangeQuery($fieldName, $from, $to);
            if ($rangeQuery) {
                $nestedBoolQuery->add($rangeQuery, BoolQuery::SHOULD);
            }
        }

        $c = $this->getClone();
        $c->boolQuery->add($nestedBoolQuery, BoolQuery::FILTER);
        return $c;
    }

    private function createRangeQuery(string $fieldName, $from = null, $to = null): ?RangeQuery
    {
        $parameters = array_filter(
            [
                RangeQuery::GTE => $from,
                RangeQuery::LTE => $to,
            ],
            function ($value) {
                return !is_null($value);
            }
        );

        if (empty($parameters)) {
            return null;
        }

        return new RangeQuery($fieldName, $parameters);
    }

    /**
     * @param string $fieldName
     * @param \DateTimeImmutable|null $from
     * @param \DateTimeImmutable|null $to
     * @return static
     */
    protected function withDateRangeQuery($fieldName, \DateTimeImmutable $from = null, \DateTimeImmutable $to = null)
    {
        return $this->withRangeQuery(
            $fieldName,
            is_null($from) ? null : $from->format(\DateTime::ATOM),
            is_null($to) ? null : $to->format(\DateTime::ATOM)
        );
    }

    /**
     * @param string[] $fieldNames
     * @param \DateTimeImmutable|null $from
     * @param \DateTimeImmutable|null $to
     * @return static
     */
    protected function withMultiFieldDateRangeQuery(
        array $fieldNames,
        \DateTimeImmutable $from = null,
        \DateTimeImmutable $to = null
    ) {
        return $this->withMultiFieldRangeQuery(
            $fieldNames,
            is_null($from) ? null : $from->format(\DateTime::ATOM),
            is_null($to) ? null : $to->format(\DateTime::ATOM)
        );
    }

    /**
     * @param string $queryString
     * @param string[] $fields
     * @param string $type
     * @param string $defaultOperator
     * @return AbstractElasticSearchQueryBuilder
     */
    protected function withQueryStringQuery(
        $queryString,
        array $fields = [],
        $type = BoolQuery::MUST,
        $defaultOperator = 'OR'
    ) {
        $parameters = [];
        if (!empty($fields)) {
            $parameters['fields'] = $fields;
        }
        if ('OR' != \strtoupper($defaultOperator)) {
            $parameters['default_operator'] = $defaultOperator;
        }

        $queryStringQuery = new QueryStringQuery($queryString, $parameters);

        $c = $this->getClone();
        $c->boolQuery->add($queryStringQuery, $type);
        return $c;
    }

    /**
     * @return Language[]
     */
    protected function getDefaultLanguages()
    {
        return [
            new Language('nl'),
            new Language('fr'),
            new Language('en'),
            new Language('de'),
        ];
    }

    /**
     * @param string $field
     * @param string $order
     * @param array $parameters
     * @return static
     */
    protected function withFieldSort($field, $order, $parameters = [])
    {
        $sort = new FieldSort($field, $order, $parameters);

        $c = $this->getClone();
        $c->search->addSort($sort);
        return $c;
    }

    public function withShardPreference(string $preference): self
    {
        $queryBuilder = clone $this;
        $queryBuilder->shardPreference = $preference;
        return $queryBuilder;
    }

    public function createUrlParameters(): array
    {
        $parameters = [];

        if ($this->shardPreference !== null) {
            $parameters['preference'] = $this->shardPreference;
        }

        return $parameters;
    }
}
