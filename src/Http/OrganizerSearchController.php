<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerQueryBuilder;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\OrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Parameters\OrganizerSupportedParameters;
use CultuurNet\UDB3\Search\Http\Parameters\ParameterBagInterface;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerSearchServiceInterface;
use CultuurNet\UDB3\Search\QueryStringFactory;
use CultuurNet\UDB3\Search\Start;
use Psr\Http\Message\ResponseInterface;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Domain;
use ValueObjects\Web\Url;

final class OrganizerSearchController
{
    /**
     * @var ApiKeyReaderInterface
     */
    private $apiKeyReader;

    /**
     * @var OrganizerQueryBuilderInterface
     */
    private $queryBuilder;

    /**
     * @var OrganizerSearchServiceInterface
     */
    private $searchService;

    /**
     * @var OrganizerSupportedParameters
     */
    private $organizerParameterWhiteList;

    /**
     * @var QueryStringFactory
     */
    private $queryStringFactory;

    /**
     * @var OrganizerRequestParser
     */
    private $organizerRequestParser;

    public function __construct(
        ApiKeyReaderInterface $apiKeyReader,
        OrganizerQueryBuilderInterface $queryBuilder,
        OrganizerSearchServiceInterface $searchService,
        OrganizerRequestParser $organizerRequestParser,
        QueryStringFactory $queryStringFactory
    ) {
        $this->apiKeyReader = $apiKeyReader;
        $this->queryBuilder = $queryBuilder;
        $this->searchService = $searchService;
        $this->organizerRequestParser = $organizerRequestParser;
        $this->queryStringFactory = $queryStringFactory;
        $this->organizerParameterWhiteList = new OrganizerSupportedParameters();
    }

    public function __invoke(ApiRequestInterface $request): ResponseInterface
    {
        $this->organizerParameterWhiteList->guardAgainstUnsupportedParameters(
            $request->getQueryParamsKeys()
        );

        $start = new Start((int) $request->getQueryParam('start', 0));
        $limit = (int) $request->getQueryParam('limit', 30);

        if ($limit < 0 || $limit > 2000) {
            throw new \InvalidArgumentException('The "limit" parameter should be between 0 and 2000');
        }
        if ($limit === 0) {
            $limit = 30;
        }

        $parameterBag = $request->getQueryParameterBag();

        $queryBuilder = $this->queryBuilder
            ->withStart(new Natural($start->toInteger()))
            ->withLimit(new Natural($limit));

        $consumerApiKey = $this->apiKeyReader->read($request);

        if ($consumerApiKey instanceof ApiKey &&
            $queryBuilder instanceof ElasticSearchOrganizerQueryBuilder) {
            $queryBuilder = $queryBuilder->withShardPreference('consumer_' . $consumerApiKey->toNative());
        }

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

        $resultTransformer = ResultTransformerFactory::create(
            (bool) $parameterBag->getBooleanFromParameter('embed')
        );

        $pagedCollection = PagedCollectionFactory::fromPagedResultSet(
            $resultTransformer,
            $resultSet,
            $start->toInteger(),
            $limit
        );

        return ResponseFactory::jsonLd($pagedCollection);
    }


    /**
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
