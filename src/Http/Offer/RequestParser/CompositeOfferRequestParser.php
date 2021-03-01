<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

class CompositeOfferRequestParser implements OfferRequestParserInterface
{
    /**
     * @var OfferRequestParserInterface[]
     */
    private $parsers = [];

    public function __construct()
    {
        $this->parsers = [];
    }

    public function withParser(OfferRequestParserInterface $parser)
    {
        $c = clone $this;
        $c->parsers[] = $parser;
        return $c;
    }

    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        foreach ($this->parsers as $parser) {
            $offerQueryBuilder = $parser->parse($request, $offerQueryBuilder);
        }
        return $offerQueryBuilder;
    }
}
