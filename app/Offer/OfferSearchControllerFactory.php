<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Offer;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepositoryInterface;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDistanceFactory;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferQueryBuilder;
use CultuurNet\UDB3\Search\Http\NodeAwareFacetTreeNormalizer;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\AgeRangeOfferRequestParser;
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
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceFactory;
use ValueObjects\StringLiteral\StringLiteral;

class OfferSearchControllerFactory
{
    /**
     * @var int
     */
    private $aggregationSize;

    /**
     * @var string
     */
    private $regionIndex;

    /**
     * @var string
     */
    private $documentType;

    /**
     * @var ApiKeyReaderInterface
     */
    private $apiKeyReader;

    /**
     * @var ConsumerReadRepositoryInterface
     */
    private $consumerReadRepository;

    /**
     * @var OfferSearchServiceFactory
     */
    private $offerSearchServiceFactory;

    public function __construct(
        ?int $aggregationSize,
        string $regionIndex,
        string $documentType,
        ApiKeyReaderInterface $apiKeyReader,
        ConsumerReadRepositoryInterface $consumerReadRepository,
        OfferSearchServiceFactory $offerSearchServiceFactory
    ) {
        $this->aggregationSize = $aggregationSize;
        $this->regionIndex = $regionIndex;
        $this->documentType = $documentType;
        $this->apiKeyReader = $apiKeyReader;
        $this->consumerReadRepository = $consumerReadRepository;
        $this->offerSearchServiceFactory = $offerSearchServiceFactory;
    }

    public function createFor(
        string $readIndex,
        string $documentType
    ) {
        $requestParser = (new CompositeOfferRequestParser())
            ->withParser(new AgeRangeOfferRequestParser())
            ->withParser(new AvailabilityOfferRequestParser())
            ->withParser(new CalendarOfferRequestParser())
            ->withParser(new DistanceOfferRequestParser(new ElasticSearchDistanceFactory()))
            ->withParser(new DocumentLanguageOfferRequestParser())
            ->withParser(new GeoBoundsOfferRequestParser())
            ->withParser(new GroupByOfferRequestParser())
            ->withParser(new IsDuplicateOfferRequestParser())
            ->withParser(new SortByOfferRequestParser())
            ->withParser(new RelatedProductionRequestParser())
            ->withParser(new WorkflowStatusOfferRequestParser());

        return new OfferSearchController(
            $this->apiKeyReader,
            $this->consumerReadRepository,
            new ElasticSearchOfferQueryBuilder($this->aggregationSize),
            $requestParser,
            $this->offerSearchServiceFactory->createFor(
                $readIndex,
                $documentType
            ),
            new StringLiteral($this->regionIndex),
            new StringLiteral($this->documentType),
            new LuceneQueryStringFactory(),
            new NodeAwareFacetTreeNormalizer()
        );
    }
}
