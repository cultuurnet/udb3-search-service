<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\Language\Language;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

interface QueryBuilderInterface
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
    public function withTextQuery(StringLiteral $text, Language ...$textLanguages);

    /**
     * @return static
     */
    public function withStart(Natural $start);

    /**
     * @return static
     */
    public function withLimit(Natural $limit);

    public function getLimit(): Natural;

    public function build(): array;
}
