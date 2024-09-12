<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;

final class CompositeOrganizerRequestParser implements OrganizerRequestParser
{
    /**
     * @var OrganizerRequestParser[]
     */
    private array $parsers = [];

    public function __construct()
    {
        $this->parsers = [];
    }

    public function withParser(OrganizerRequestParser $parser): self
    {
        $c = clone $this;
        $c->parsers[] = $parser;
        return $c;
    }

    public function parse(
        ApiRequestInterface $request,
        OrganizerQueryBuilderInterface $organizerQueryBuilder
    ): OrganizerQueryBuilderInterface {
        foreach ($this->parsers as $parser) {
            $organizerQueryBuilder = $parser->parse($request, $organizerQueryBuilder);
        }
        return $organizerQueryBuilder;
    }
}
