<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepositoryInterface;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferQueryBuilder;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\OfferRequestParserInterface;
use CultuurNet\UDB3\Search\Http\Parameters\OfferSupportedParameters;
use CultuurNet\UDB3\Search\Http\Parameters\ParameterBagInterface;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Offer\AudienceType;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;
use CultuurNet\UDB3\Search\Offer\Cdbid;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceInterface;
use CultuurNet\UDB3\Search\Offer\TermId;
use CultuurNet\UDB3\Search\Offer\TermLabel;
use CultuurNet\UDB3\Search\PriceInfo\Price;
use CultuurNet\UDB3\Search\QueryStringFactory;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\Start;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use Psr\Http\Message\ResponseInterface;

/**
 * @todo Extract more parsing functionality to OfferRequestParserInterface
 *   implementations.
 * @see https://jira.uitdatabank.be/browse/III-2144
 */
final class OfferSearchController
{
    /**
     * @var ApiKeyReaderInterface
     */
    private $apiKeyReader;

    /**
     * @var ConsumerReadRepositoryInterface
     */
    private $consumerReadRepository;

    /**
     * @var OfferQueryBuilderInterface
     */
    private $queryBuilder;

    /**
     * @var OfferRequestParserInterface
     */
    private $requestParser;

    /**
     * @var OfferSearchServiceInterface
     */
    private $searchService;

    /**
     * @var string
     */
    private $regionIndexName;

    /**
     * @var string
     */
    private $regionDocumentType;

    /**
     * @var QueryStringFactory
     */
    private $queryStringFactory;

    /**
     * @var FacetTreeNormalizerInterface
     */
    private $facetTreeNormalizer;

    /**
     * @var OfferSupportedParameters
     */
    private $offerParameterWhiteList;

    public function __construct(
        ApiKeyReaderInterface $apiKeyReader,
        ConsumerReadRepositoryInterface $consumerReadRepository,
        OfferQueryBuilderInterface $queryBuilder,
        OfferRequestParserInterface $offerRequestParser,
        OfferSearchServiceInterface $searchService,
        string $regionIndexName,
        string $regionDocumentType,
        QueryStringFactory $queryStringFactory,
        FacetTreeNormalizerInterface $facetTreeNormalizer
    ) {
        $this->apiKeyReader = $apiKeyReader;
        $this->consumerReadRepository = $consumerReadRepository;
        $this->queryBuilder = $queryBuilder;
        $this->requestParser = $offerRequestParser;
        $this->searchService = $searchService;
        $this->regionIndexName = $regionIndexName;
        $this->regionDocumentType = $regionDocumentType;
        $this->queryStringFactory = $queryStringFactory;
        $this->facetTreeNormalizer = $facetTreeNormalizer;
        $this->offerParameterWhiteList = new OfferSupportedParameters();
    }

    /**
     * @return ResponseInterface
     */
    public function __invoke(ApiRequest $request)
    {
        $this->offerParameterWhiteList->guardAgainstUnsupportedParameters(
            $request->getQueryParamsKeys()
        );

        $start = new Start((int) $request->getQueryParam('start', 0));
        $limit = new Limit((int) $request->getQueryParam('limit', 30));

        $queryBuilder = $this->queryBuilder
            ->withStart($start)
            ->withLimit($limit);

        $consumerApiKey = $this->apiKeyReader->read($request);

        if ($consumerApiKey instanceof ApiKey &&
            $queryBuilder instanceof ElasticSearchOfferQueryBuilder) {
            $queryBuilder = $queryBuilder->withShardPreference('consumer_' . $consumerApiKey->toString());
        }

        $queryBuilder = $this->requestParser->parse($request, $queryBuilder);

        $parameterBag = $request->getQueryParameterBag();

        $textLanguages = $this->getLanguagesFromQuery($parameterBag, 'textLanguages');

        $consumer = $consumerApiKey ? $this->consumerReadRepository->getConsumer($consumerApiKey) : null;
        $defaultQuery = $consumer ? $consumer->getDefaultQuery() : null;
        if ($defaultQuery) {
            $queryBuilder = $queryBuilder->withAdvancedQuery(
                $this->queryStringFactory->fromString($defaultQuery),
                ...$textLanguages
            );
        }

        if ($request->hasQueryParam('q')) {
            $queryBuilder = $queryBuilder->withAdvancedQuery(
                $this->queryStringFactory->fromString(
                    $request->getQueryParam('q')
                ),
                ...$textLanguages
            );
        }

        if ($request->hasQueryParam('text')) {
            $queryBuilder = $queryBuilder->withTextQuery(
                $request->getQueryParam('text'),
                ...$textLanguages
            );
        }

        if ($request->hasQueryParam('id')) {
            $queryBuilder = $queryBuilder->withCdbIdFilter(
                new Cdbid($request->getQueryParam('id'))
            );
        }

        if ($request->hasQueryParam('locationId')) {
            $queryBuilder = $queryBuilder->withLocationCdbIdFilter(
                new Cdbid($request->getQueryParam('locationId'))
            );
        }

        if ($request->hasQueryParam('organizerId')) {
            $queryBuilder = $queryBuilder->withOrganizerCdbIdFilter(
                new Cdbid($request->getQueryParam('organizerId'))
            );
        }

        $regionIds = $this->getRegionIdsFromQuery($parameterBag, 'regions');
        foreach ($regionIds as $regionId) {
            $queryBuilder = $queryBuilder->withRegionFilter(
                $this->regionIndexName,
                $this->regionDocumentType,
                $regionId
            );
        }

        $postalCode = (string) $request->getQueryParam('postalCode');
        if (!empty($postalCode)) {
            $queryBuilder = $queryBuilder->withPostalCodeFilter(
                new PostalCode($postalCode)
            );
        }

        $country = (new CountryExtractor())->getCountryFromQuery(
            $parameterBag,
            new Country('BE')
        );
        if (!empty($country)) {
            $queryBuilder = $queryBuilder->withAddressCountryFilter($country);
        }

        $audienceType = $this->getAudienceTypeFromQuery($parameterBag);
        if (!empty($audienceType)) {
            $queryBuilder = $queryBuilder->withAudienceTypeFilter($audienceType);
        }

        $price = $request->getQueryParam('price', null);
        $minPrice = $request->getQueryParam('minPrice', null);
        $maxPrice = $request->getQueryParam('maxPrice', null);

        if (!is_null($price)) {
            $price = Price::fromFloat((float) $price);
            $queryBuilder = $queryBuilder->withPriceRangeFilter($price, $price);
        } elseif (!is_null($minPrice) || !is_null($maxPrice)) {
            $minPrice = is_null($minPrice) ? null : Price::fromFloat((float) $minPrice);
            $maxPrice = is_null($maxPrice) ? null : Price::fromFloat((float) $maxPrice);

            $queryBuilder = $queryBuilder->withPriceRangeFilter($minPrice, $maxPrice);
        }

        $includeMediaObjects = $parameterBag->getBooleanFromParameter('hasMediaObjects');
        if (!is_null($includeMediaObjects)) {
            $queryBuilder = $queryBuilder->withMediaObjectsFilter($includeMediaObjects);
        }

        $includeUiTPAS = $parameterBag->getBooleanFromParameter('uitpas');
        if (!is_null($includeUiTPAS)) {
            $queryBuilder = $queryBuilder->withUiTPASFilter($includeUiTPAS);
        }

        if ($request->hasQueryParam('creator')) {
            $queryBuilder = $queryBuilder->withCreatorFilter(
                new Creator($request->getQueryParam('creator'))
            );
        }

        $createdFrom = $parameterBag->getDateTimeFromParameter('createdFrom');
        $createdTo = $parameterBag->getDateTimeFromParameter('createdTo');
        if ($createdFrom || $createdTo) {
            $queryBuilder = $queryBuilder->withCreatedRangeFilter($createdFrom, $createdTo);
        }

        $modifiedFrom = $parameterBag->getDateTimeFromParameter('modifiedFrom');
        $modifiedTo = $parameterBag->getDateTimeFromParameter('modifiedTo');
        if ($modifiedFrom || $modifiedTo) {
            $queryBuilder = $queryBuilder->withModifiedRangeFilter($modifiedFrom, $modifiedTo);
        }

        $termIds = $this->getTermIdsFromQuery($parameterBag, 'termIds');
        foreach ($termIds as $termId) {
            $queryBuilder = $queryBuilder->withTermIdFilter($termId);
        }

        $termLabels = $this->getTermLabelsFromQuery($parameterBag, 'termLabels');
        foreach ($termLabels as $termLabel) {
            $queryBuilder = $queryBuilder->withTermLabelFilter($termLabel);
        }

        $locationTermIds = $this->getTermIdsFromQuery($parameterBag, 'locationTermIds');
        foreach ($locationTermIds as $locationTermId) {
            $queryBuilder = $queryBuilder->withLocationTermIdFilter($locationTermId);
        }

        $locationTermLabels = $this->getTermLabelsFromQuery($parameterBag, 'locationTermLabels');
        foreach ($locationTermLabels as $locationTermLabel) {
            $queryBuilder = $queryBuilder->withLocationTermLabelFilter($locationTermLabel);
        }

        $labels = $this->getLabelsFromQuery($parameterBag, 'labels');
        foreach ($labels as $label) {
            $queryBuilder = $queryBuilder->withLabelFilter($label);
        }

        $locationLabels = $this->getLabelsFromQuery($parameterBag, 'locationLabels');
        foreach ($locationLabels as $locationLabel) {
            $queryBuilder = $queryBuilder->withLocationLabelFilter($locationLabel);
        }

        $organizerLabels = $this->getLabelsFromQuery($parameterBag, 'organizerLabels');
        foreach ($organizerLabels as $organizerLabel) {
            $queryBuilder = $queryBuilder->withOrganizerLabelFilter($organizerLabel);
        }

        $facets = $this->getFacetsFromQuery($parameterBag, 'facets');
        foreach ($facets as $facet) {
            $queryBuilder = $queryBuilder->withFacet($facet);
        }
        $resultSet = $this->searchService->search($queryBuilder);

        $calendarSummaries = array_map(
            function (string $parameter) {
                return CalendarSummaryFormat::fromCombinedParameter($parameter);
            },
            $parameterBag->getArrayFromParameter('embedCalendarSummaries')
        );

        $resultTransformer = ResultTransformerFactory::create(
            (bool) $parameterBag->getBooleanFromParameter('embed'),
            ...$calendarSummaries
        );

        $pagedCollection = PagedCollectionFactory::fromPagedResultSet(
            $resultTransformer,
            $resultSet,
            $start->toInteger(),
            $limit->toInteger()
        );

        $jsonArray = $pagedCollection->jsonSerialize();

        foreach ($resultSet->getFacets() as $facetFilter) {
            // Singular "facet" to be consistent with "member" in Hydra
            // PagedCollection.
            $jsonArray['facet'][$facetFilter->getKey()] = $this->facetTreeNormalizer->normalize($facetFilter);
        }

        return ResponseFactory::jsonLd($jsonArray);
    }

    /**
     * @param string $queryParameter
     * @return TermId[]
     */
    private function getTermIdsFromQuery(ParameterBagInterface $parameterBag, $queryParameter)
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            function ($value) {
                return new TermId($value);
            }
        );
    }

    /**
     * @param string $queryParameter
     * @return TermLabel[]
     */
    private function getTermLabelsFromQuery(ParameterBagInterface $parameterBag, $queryParameter)
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            function ($value) {
                return new TermLabel($value);
            }
        );
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

    /**
     * @param string $queryParameter
     * @return RegionId[]
     */
    private function getRegionIdsFromQuery(ParameterBagInterface $parameterBag, $queryParameter)
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            function ($value) {
                return new RegionId($value);
            }
        );
    }

    /**
     * @return AudienceType|null
     */
    private function getAudienceTypeFromQuery(ParameterBagInterface $parameterBag)
    {
        return $parameterBag->getStringFromParameter(
            'audienceType',
            'everyone',
            function ($audienceType) {
                return new AudienceType($audienceType);
            }
        );
    }

    /**
     * @return FacetName[]
     */
    private function getFacetsFromQuery(ParameterBagInterface $parameterBag, string $queryParameter): array
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            function ($value) {
                try {
                    return new FacetName(strtolower($value));
                } catch (UnsupportedParameterValue $e) {
                    throw new UnsupportedParameterValue("Unknown facet name '$value'.");
                }
            }
        );
    }
}
