<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use DateTimeImmutable;
use DateTime;
use CultuurNet\UDB3\Search\AbstractQueryString;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Natural;
use CultuurNet\UDB3\Search\QueryBuilder;
use CultuurNet\UDB3\Search\Start;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhraseQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

abstract class AbstractElasticSearchQueryBuilder implements QueryBuilder
{
    protected Search $search;

    protected BoolQuery $boolQuery;

    private ?string $shardPreference = null;

    protected array $extraQueryParameters = [];

    public function __construct()
    {
        $this->boolQuery = new BoolQuery();
        $this->boolQuery->add(new MatchAllQuery(), BoolQuery::MUST);

        $this->search = new Search();
        $this->search->addQuery($this->boolQuery);

        $this->search->setFrom(0);
        $this->search->setSize(30);
    }

    /**
     * @return static
     */
    public function withAdvancedQuery(AbstractQueryString $queryString, Language ...$textLanguages)
    {
        if (empty($textLanguages)) {
            $textLanguages = $this->getDefaultLanguages();
        }

        return $this->withQueryStringQuery(
            $queryString->toString(),
            $this->getPredefinedQueryStringFields(...$textLanguages)
        );
    }

    /**
     * @return static
     */
    public function withTextQuery(string $text, Language ...$textLanguages): self
    {
        if (empty($textLanguages)) {
            $textLanguages = $this->getDefaultLanguages();
        }

        return $this->withQueryStringQuery(
            str_replace(':', '\\:', $text),
            $this->getPredefinedQueryStringFields(...$textLanguages),
            BoolQuery::MUST,
            'AND'
        );
    }

    public function withStartAndLimit(Start $start, Limit $limit)
    {
        $c = $this->getClone();
        $c->search->setFrom($start->toInteger());
        $c->search->setSize($limit->toInteger());
        $c->guardResultWindowLimit();
        return $c;
    }

    private function guardResultWindowLimit(): void
    {
        $window = $this->search->getFrom() + $this->search->getSize();
        if ($window > 10000) {
            throw new UnsupportedParameterValue('Parameters start + limit must be less than or equal to 10000, got ' . $window . '.');
        }
    }

    public function getLimit(): Limit
    {
        $size = $this->search->getSize();
        return $size ? new Limit($size) : new Limit(QueryBuilder::DEFAULT_LIMIT);
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
     * @throws UnsupportedParameterValue
     */
    protected function guardNaturalIntegerRange(
        $parameterName,
        Natural $min = null,
        Natural $max = null
    ) {
        if (!is_null($min) && !is_null($max) && $min->toNative() > $max->toNative()) {
            throw new UnsupportedParameterValue(
                "Minimum {$parameterName} should be smaller or equal to maximum {$parameterName}."
            );
        }
    }

    /**
     * @param string $parameterName
     * @throws UnsupportedParameterValue
     */
    protected function guardDateRange(
        $parameterName,
        DateTimeImmutable $from = null,
        DateTimeImmutable $to = null
    ) {
        if (!is_null($from) && !is_null($to) && $from > $to) {
            throw new UnsupportedParameterValue(
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
     * Adds a MATCH query for one or more terms. If multiple terms are supplied, a SHOULD boolean query is used,
     * which is the same as an OR operator. So this query is useful to find all documents with a single-value field that
     * should contain one of the given terms as a value.
     *
     * @see self::createMultiValueMatchQuery()
     *
     * @param string[] $terms
     * @return static
     */
    protected function withMultiValueMatchQuery(string $fieldName, array $terms)
    {
        if (empty($terms)) {
            return $this;
        }

        $query = $this->createMultiValueMatchQuery($fieldName, $terms);

        $c = $this->getClone();
        $c->boolQuery->add($query, BoolQuery::FILTER);
        return $c;
    }

    /**
     * Creates a MATCH query for one or more terms. If multiple terms are supplied, a SHOULD boolean query is used,
     * which is the same as an OR operator. So this query is useful to find all documents with a single-value field that
     * should contain one of the given terms as a value.
     */
    protected function createMultiValueMatchQuery(string $fieldName, array $terms): BuilderInterface
    {
        if (count($terms) === 1) {
            return new MatchQuery($fieldName, $terms[0]);
        }

        $boolQuery = new BoolQuery();
        foreach ($terms as $term) {
            $boolQuery->add(new MatchQuery($fieldName, $term), BoolQuery::SHOULD);
        }
        return $boolQuery;
    }

    /**
     * @param string[] $fieldNames
     * @return static
     */
    protected function withMultiFieldMatchQuery(array $fieldNames, string $term)
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
     * @return static
     */
    protected function withMatchPhraseQuery(string $fieldName, string $term)
    {
        $matchPhraseQuery = new MatchPhraseQuery($fieldName, $term);

        $c = $this->getClone();
        $c->boolQuery->add($matchPhraseQuery, BoolQuery::FILTER);
        $c->boolQuery->add($matchPhraseQuery, BoolQuery::SHOULD);
        return $c;
    }

    /**
     * @param string|int|float|null $from
     * @param string|int|float|null $to
     * @return static
     */
    protected function withRangeQuery(string $fieldName, $from = null, $to = null)
    {
        $rangeQuery = $this->createRangeQuery($fieldName, $from, $to);
        if (!$rangeQuery) {
            return $this;
        }

        $c = $this->getClone();
        $c->boolQuery->add($rangeQuery, BoolQuery::FILTER);
        return $c;
    }

    protected function createRangeQuery(string $fieldName, $from = null, $to = null): ?RangeQuery
    {
        $parameters = array_filter(
            [
                RangeQuery::GTE => $from,
                RangeQuery::LTE => $to,
            ],
            fn ($value): bool => !is_null($value)
        );

        if (empty($parameters)) {
            return null;
        }

        return new RangeQuery($fieldName, $parameters);
    }

    /**
     * @return static
     */
    protected function withDateRangeQuery(string $fieldName, DateTimeImmutable $from = null, DateTimeImmutable $to = null)
    {
        return $this->withRangeQuery(
            $fieldName,
            is_null($from) ? null : $from->format(DateTime::ATOM),
            is_null($to) ? null : $to->format(DateTime::ATOM)
        );
    }

    /**
     * @param string[] $fields
     * @return static
     */
    protected function withQueryStringQuery(
        string $queryString,
        array $fields = [],
        string $type = BoolQuery::MUST,
        string $defaultOperator = 'OR'
    ) {
        $parameters = [];
        if (!empty($fields)) {
            $parameters['fields'] = $fields;
        }
        if ('OR' !== \strtoupper($defaultOperator)) {
            $parameters['default_operator'] = $defaultOperator;
        }

        $queryStringQuery = new QueryStringQuery($queryString, $parameters);

        $c = $this->getClone();
        $c->boolQuery->add($queryStringQuery, $type);
        return $c;
    }

    /**
     * @return static
     */
    protected function withBooleanFilterQueryOnNestedObject(string $path, BuilderInterface ...$queries)
    {
        $boolQuery = new BoolQuery();
        foreach ($queries as $individualQuery) {
            $boolQuery->add($individualQuery, BoolQuery::FILTER);
        }

        $c = $this->getClone();
        $c->boolQuery->add(new NestedQuery($path, $boolQuery), BoolQuery::FILTER);
        return $c;
    }

    /**
     * @return Language[]
     */
    protected function getDefaultLanguages(): array
    {
        return [
            new Language('nl'),
            new Language('fr'),
            new Language('en'),
            new Language('de'),
        ];
    }

    /**
     * @return static
     */
    protected function withFieldSort(string $field, string $order, array $parameters = []): AbstractElasticSearchQueryBuilder
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
