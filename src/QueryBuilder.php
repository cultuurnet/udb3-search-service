<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\Language\Language;

interface QueryBuilder
{
    public const DEFAULT_LIMIT = 10;

    /**
     * @param Language ...$textLanguages
     * @return static
     */
    public function withAdvancedQuery(AbstractQueryString $queryString, Language ...$textLanguages);

    /**
     * @param Language ...$textLanguages
     * @return static
     */
    public function withTextQuery(string $text, Language ...$textLanguages);

    /**
     * @return static
     */
    public function withStartAndLimit(Start $start, Limit $limit);

    public function getLimit(): Limit;

    public function build(): array;
}
