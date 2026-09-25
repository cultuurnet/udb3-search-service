<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound;

enum BoolClause: string
{
    case Must = 'must';
    case Filter = 'filter';
    case Should = 'should';
    case MustNot = 'must_not';
}
