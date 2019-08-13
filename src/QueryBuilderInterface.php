<?php

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Language;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

interface QueryBuilderInterface
{
    /**
     * @param AbstractQueryString $queryString
     * @param Language[] $textLanguages
     * @return static
     */
    public function withAdvancedQuery(AbstractQueryString $queryString, Language ...$textLanguages);

    /**
     * @param StringLiteral $text
     * @param Language[] $textLanguages
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

    /**
     * @return mixed
     *   Return type depends on the implementation.
     */
    public function build();
}
