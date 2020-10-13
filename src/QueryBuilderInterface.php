<?php

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\Language\Language;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

interface QueryBuilderInterface
{
    public const DEFAULT_LIMIT = 10;

    /**
     * @param AbstractQueryString $queryString
     * @param Language ...$textLanguages
     * @return static
     */
    public function withAdvancedQuery(AbstractQueryString $queryString, Language ...$textLanguages);

    /**
     * @param StringLiteral $text
     * @param Language ...$textLanguages
     * @return static
     */
    public function withTextQuery(StringLiteral $text, Language ...$textLanguages);

    /**
     * @param Natural $start
     * @return static
     */
    public function withStart(Natural $start);

    /**
     * @param Natural $limit
     * @return static
     */
    public function withLimit(Natural $limit);

    public function getLimit(): Natural;

    /**
     * @return mixed
     *   Return type depends on the implementation.
     */
    public function build();
}
