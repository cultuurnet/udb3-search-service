<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDistanceFactory;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferQueryBuilder;
use CultuurNet\UDB3\Search\Http\Authentication\Consumer;
use CultuurNet\UDB3\Search\Http\NodeAwareFacetTreeNormalizer;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\AgeRangeOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\AttendanceModeOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\AvailabilityOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\CalendarOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\CompositeOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\DistanceOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\DocumentLanguageOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\GeoBoundsOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\GroupByOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\IsDuplicateOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\RelatedProductionRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\SortByOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\WorkflowStatusOfferRequestParser;
use CultuurNet\UDB3\Search\Http\OfferSearchController;
use CultuurNet\UDB3\Search\Http\Parameters\GeoBoundsParametersFactory;
use CultuurNet\UDB3\Search\Http\Parameters\GeoDistanceParametersFactory;
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceFactory;

final class OfferSearchControllerFactory
{
    private ?int $aggregationSize;

    private string $regionIndex;

    private string $documentType;

    private OfferSearchServiceFactory $offerSearchServiceFactory;

    private Consumer $consumer;

    public function __construct(
        ?int $aggregationSize,
        string $regionIndex,
        string $documentType,
        OfferSearchServiceFactory $offerSearchServiceFactory,
        Consumer $consumer
    ) {
        $this->aggregationSize = $aggregationSize;
        $this->regionIndex = $regionIndex;
        $this->documentType = $documentType;
        $this->offerSearchServiceFactory = $offerSearchServiceFactory;
        $this->consumer = $consumer;
    }

    public function createFor(
        string $readIndex,
        string $documentType
    ): OfferSearchController {
        $requestParser = (new CompositeOfferRequestParser())
            ->withParser(new AgeRangeOfferRequestParser())
            ->withParser(new AvailabilityOfferRequestParser())
            ->withParser(new CalendarOfferRequestParser())
            ->withParser(new AttendanceModeOfferRequestParser())
            ->withParser(new DistanceOfferRequestParser(
                new GeoDistanceParametersFactory(new ElasticSearchDistanceFactory())
            ))
            ->withParser(new DocumentLanguageOfferRequestParser())
            ->withParser(new GeoBoundsOfferRequestParser(
                new GeoBoundsParametersFactory()
            ))
            ->withParser(new GroupByOfferRequestParser())
            ->withParser(new IsDuplicateOfferRequestParser())
            ->withParser(new SortByOfferRequestParser())
            ->withParser(new RelatedProductionRequestParser())
            ->withParser(new WorkflowStatusOfferRequestParser());

        return new OfferSearchController(
            new ElasticSearchOfferQueryBuilder($this->aggregationSize),
            $requestParser,
            $this->offerSearchServiceFactory->createFor(
                $readIndex,
                $documentType
            ),
            $this->regionIndex,
            $this->documentType,
            new LuceneQueryStringFactory(),
            new NodeAwareFacetTreeNormalizer(),
            $this->consumer
        );
    }
}
