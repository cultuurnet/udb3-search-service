<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CompositeOrganizerRequestParser implements OrganizerRequestParser
{
    /**
     * @var OrganizerRequestParser[]
     */
    private $parsers = [];

    public function __construct()
    {
        $this->parsers = [];
    }

    public function withParser(OrganizerRequestParser $parser)
    {
        $c = clone $this;
        $c->parsers[] = $parser;
        return $c;
    }

    public function parse(
        ServerRequestInterface $request,
        OrganizerQueryBuilderInterface $organizerQueryBuilder
    ): OrganizerQueryBuilderInterface {
        foreach ($this->parsers as $parser) {
            $organizerQueryBuilder = $parser->parse($request, $organizerQueryBuilder);
        }
        return $organizerQueryBuilder;
    }
}
