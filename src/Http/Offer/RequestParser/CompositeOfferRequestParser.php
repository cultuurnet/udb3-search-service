<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class CompositeOfferRequestParser implements OfferRequestParserInterface
{
    /**
     * @var OfferRequestParserInterface[]
     */
    private array $parsers = [];

    public function __construct()
    {
        $this->parsers = [];
    }

    public function withParser(OfferRequestParserInterface $parser): self
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
