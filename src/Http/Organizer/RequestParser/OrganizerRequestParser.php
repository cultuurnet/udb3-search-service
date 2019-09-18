<?php

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;

interface OrganizerRequestParser
{
    public function parse(
        ServerRequestInterface $request,
        OrganizerQueryBuilderInterface $organizerQueryBuilder
    ): OrganizerQueryBuilderInterface;
}
