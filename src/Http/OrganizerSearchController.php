<?php

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Http\Parameters\ArrayParameterBagAdapter;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\OrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Parameters\OrganizerParameterWhiteList;
use CultuurNet\UDB3\Search\Http\Parameters\ParameterBagInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerSearchServiceInterface;
use CultuurNet\UDB3\Search\QueryStringFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Domain;
use ValueObjects\Web\Url;

class OrganizerSearchController
{
    /**
     * @var OrganizerQueryBuilderInterface
     */
    private $queryBuilder;

    /**
     * @var OrganizerSearchServiceInterface
     */
    private $searchService;

    /**
     * @var ResultTransformingPagedCollectionFactoryFactory
     */
    private $resultTransformingPagedCollectionFactoryFactory;

    /**
     * @var OrganizerParameterWhiteList
     */
    private $organizerParameterWhiteList;

    /**
     * @var QueryStringFactoryInterface
     */
    private $queryStringFactory;

    /**
     * @var OrganizerRequestParser
     */
    private $organizerRequestParser;

    public function __construct(
        OrganizerQueryBuilderInterface $queryBuilder,
        OrganizerSearchServiceInterface $searchService,
        OrganizerRequestParser $organizerRequestParser,
        QueryStringFactoryInterface $queryStringFactory,
        ResultTransformingPagedCollectionFactoryFactory $resultTransformingPagedCollectionFactoryFactory
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->searchService = $searchService;
        $this->organizerRequestParser = $organizerRequestParser;
        $this->queryStringFactory = $queryStringFactory;
        $this->resultTransformingPagedCollectionFactoryFactory = $resultTransformingPagedCollectionFactoryFactory;
        $this->organizerParameterWhiteList = new OrganizerParameterWhiteList();
    }

    public function __invoke(ApiRequestInterface $request): ResponseInterface
    {
        $this->organizerParameterWhiteList->validateParameters(
            array_keys($request->getQueryParams())
        );

        $start = (int) $request->getQueryParam('start', 0);
        $limit = (int) $request->getQueryParam('limit', 30);

        if ($limit === 0) {
            $limit = 30;
        }

        $parameterBag = new ArrayParameterBagAdapter($request->getQueryParams());

        $queryBuilder = $this->queryBuilder
            ->withStart(new Natural($start))
            ->withLimit(new Natural($limit));

        $queryBuilder = $this->organizerRequestParser->parse($request, $queryBuilder);

        $textLanguages = $this->getLanguagesFromQuery($parameterBag, 'textLanguages');

        if ($request->hasQueryParam('q')) {
            $queryBuilder = $queryBuilder->withAdvancedQuery(
                $this->queryStringFactory->fromString($request->getQueryParam('q')),
                ...$textLanguages
            );
        }

        if ($request->hasQueryParam('name')) {
            $queryBuilder = $queryBuilder->withAutoCompleteFilter(
                new StringLiteral($request->getQueryParam('name'))
            );
        }

        if ($request->hasQueryParam('website')) {
            $queryBuilder = $queryBuilder->withWebsiteFilter(
                Url::fromNative($request->getQueryParam('website'))
            );
        }

        if ($request->hasQueryParam('domain')) {
            $queryBuilder = $queryBuilder->withDomainFilter(
                Domain::specifyType($request->getQueryParam('domain'))
            );
        }

        if ($request->hasQueryParam('postalCode')) {
            $queryBuilder = $queryBuilder->withPostalCodeFilter(
                new PostalCode((string) $request->getQueryParam('postalCode'))
            );
        }
        $country = (new CountryExtractor())->getCountryFromQuery(
            $parameterBag,
            null
        );
        if (!empty($country)) {
            $queryBuilder = $queryBuilder->withAddressCountryFilter($country);
        }
        if ($request->hasQueryParam('creator')) {
            $queryBuilder = $queryBuilder->withCreatorFilter(
                new Creator($request->getQueryParam('creator'))
            );
        }

        $labels = $this->getLabelsFromQuery($parameterBag, 'labels');
        foreach ($labels as $label) {
            $queryBuilder = $queryBuilder->withLabelFilter($label);
        }

        $resultSet = $this->searchService->search($queryBuilder);

        $pagedCollection = $this->resultTransformingPagedCollectionFactoryFactory
            ->create((bool) $parameterBag->getBooleanFromParameter('embed'))
            ->fromPagedResultSet(
                $resultSet,
                $start,
                $limit
            );

        /**
         * @todo add cache control to headers
         */
        return ResponseFactory::jsonLd($pagedCollection);
//        return (new JsonResponse($pagedCollection, 200, ['Content-Type' => 'application/ld+json']))
//            ->setPublic()
//            ->setClientTtl(60 * 1)
//            ->setTtl(60 * 5);
    }


    /**
     * @param ParameterBagInterface $parameterBag
     * @param string $queryParameter
     * @return LabelName[]
     */
    private function getLabelsFromQuery(ParameterBagInterface $parameterBag, $queryParameter)
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            function ($value) {
                return new LabelName($value);
            }
        );
    }

    /**
     * @param ParameterBagInterface $parameterBag
     * @param string $queryParameter
     * @return Language[]
     */
    private function getLanguagesFromQuery(ParameterBagInterface $parameterBag, $queryParameter)
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            function ($value) {
                return new Language($value);
            }
        );
    }
}
