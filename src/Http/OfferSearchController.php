<?php

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepositoryInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferQueryBuilder;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\PriceInfo\Price;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\OfferRequestParserInterface;
use CultuurNet\UDB3\Search\Http\Parameters\OfferParameterWhiteList;
use CultuurNet\UDB3\Search\Http\Parameters\ParameterBagInterface;
use CultuurNet\UDB3\Search\Offer\AudienceType;
use CultuurNet\UDB3\Search\Offer\CalendarType;
use CultuurNet\UDB3\Search\Offer\Cdbid;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceInterface;
use CultuurNet\UDB3\Search\Offer\TermId;
use CultuurNet\UDB3\Search\Offer\TermLabel;
use CultuurNet\UDB3\Search\QueryStringFactoryInterface;
use CultuurNet\UDB3\Search\Region\RegionId;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use ValueObjects\Geography\CountryCode;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @todo Extract more parsing functionality to OfferRequestParserInterface
 *   implementations.
 * @see https://jira.uitdatabank.be/browse/III-2144
 */
class OfferSearchController
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
     * @var StringLiteral
     */
    private $regionIndexName;

    /**
     * @var StringLiteral
     */
    private $regionDocumentType;

    /**
     * @var QueryStringFactoryInterface
     */
    private $queryStringFactory;

    /**
     * @var FacetTreeNormalizerInterface
     */
    private $facetTreeNormalizer;

    /**
     * @var OfferParameterWhiteList
     */
    private $offerParameterWhiteList;

    /**
     * @var ResultTransformingPagedCollectionFactoryFactory
     */
    private $resultTransformingPagedCollectionFactoryFactory;

    /**
     * @param ApiKeyReaderInterface $apiKeyReader
     * @param ConsumerReadRepositoryInterface $consumerReadRepository
     * @param OfferQueryBuilderInterface $queryBuilder
     * @param OfferRequestParserInterface $offerRequestParser
     * @param OfferSearchServiceInterface $searchService
     * @param StringLiteral $regionIndexName
     * @param StringLiteral $regionDocumentType
     * @param QueryStringFactoryInterface $queryStringFactory
     * @param FacetTreeNormalizerInterface $facetTreeNormalizer
     * @param ResultTransformingPagedCollectionFactoryFactory $resultTransformingPagedCollectionFactoryFactory
     */
    public function __construct(
        ApiKeyReaderInterface $apiKeyReader,
        ConsumerReadRepositoryInterface $consumerReadRepository,
        OfferQueryBuilderInterface $queryBuilder,
        OfferRequestParserInterface $offerRequestParser,
        OfferSearchServiceInterface $searchService,
        StringLiteral $regionIndexName,
        StringLiteral $regionDocumentType,
        QueryStringFactoryInterface $queryStringFactory,
        FacetTreeNormalizerInterface $facetTreeNormalizer,
        ResultTransformingPagedCollectionFactoryFactory $resultTransformingPagedCollectionFactoryFactory
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
        $this->offerParameterWhiteList = new OfferParameterWhiteList();
        $this->resultTransformingPagedCollectionFactoryFactory = $resultTransformingPagedCollectionFactoryFactory;
    }

    /**
     * @param ApiRequest $request
     * @return ResponseInterface
     */
    public function __invoke(ApiRequest $request)
    {
        $this->offerParameterWhiteList->validateParameters(
            $request->getQueryParamsKeys()
        );

        $start = (int) $request->getQueryParam('start', 0);
        $limit = (int) $request->getQueryParam('limit', 30);

        if ($limit === 0) {
            $limit = 30;
        }
        $queryBuilder = $this->queryBuilder
            ->withStart(new Natural($start))
            ->withLimit(new Natural($limit));

        $consumerApiKey = $this->apiKeyReader->read($request);

        if ($consumerApiKey instanceof ApiKey &&
            $queryBuilder instanceof ElasticSearchOfferQueryBuilder) {
            $queryBuilder = $queryBuilder->withShardPreference('consumer_' . $consumerApiKey->toNative());
        }

        $queryBuilder = $this->requestParser->parse($request, $queryBuilder);

        $parameterBag = $request->getQueryParameterBag();

        $textLanguages = $this->getLanguagesFromQuery($parameterBag, 'textLanguages');

        $consumer = $consumerApiKey ? $this->consumerReadRepository->getConsumer($consumerApiKey) : null;
        $defaultQuery = $consumer ? $consumer->getDefaultQuery() : null;
        if ($defaultQuery) {
            $queryBuilder = $queryBuilder->withAdvancedQuery(
                $this->queryStringFactory->fromString(
                    $defaultQuery->toNative()
                ),
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
                new StringLiteral($request->getQueryParam('text')),
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

        $availableFrom = $this->getAvailabilityFromQuery($request, 'availableFrom');
        $availableTo = $this->getAvailabilityFromQuery($request, 'availableTo');
        if ($availableFrom || $availableTo) {
            $queryBuilder = $queryBuilder->withAvailableRangeFilter($availableFrom, $availableTo);
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
            CountryCode::fromNative('BE')
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

        $calendarTypes = $this->getCalendarTypesFromQuery($parameterBag);
        if (!empty($calendarTypes)) {
            $queryBuilder = $queryBuilder->withCalendarTypeFilter(...$calendarTypes);
        }

        $dateFrom = $parameterBag->getDateTimeFromParameter('dateFrom');
        $dateTo = $parameterBag->getDateTimeFromParameter('dateTo');
        if ($dateFrom || $dateTo) {
            $queryBuilder = $queryBuilder->withDateRangeFilter($dateFrom, $dateTo);
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

        $resultTransformingPagedCollectionFactory = $this->resultTransformingPagedCollectionFactoryFactory->create(
            (bool) $parameterBag->getBooleanFromParameter('embed')
        );

        $pagedCollection = $resultTransformingPagedCollectionFactory->fromPagedResultSet(
            $resultSet,
            $start,
            $limit
        );

        $jsonArray = $pagedCollection->jsonSerialize();

        foreach ($resultSet->getFacets() as $facetFilter) {
            // Singular "facet" to be consistent with "member" in Hydra
            // PagedCollection.
            $jsonArray['facet'][$facetFilter->getKey()] = $this->facetTreeNormalizer->normalize($facetFilter);
        }

        return ResponseFactory::jsonLd($jsonArray);
    }

    private function getAvailabilityFromQuery(ApiRequestInterface $request, string $queryParameter): ?DateTimeImmutable
    {
        $defaultDateTime = DateTimeImmutable::createFromFormat('U', $request->getServerParam('REQUEST_TIME'));
        $defaultDateTimeString = ($defaultDateTime) ? $defaultDateTime->format(\DateTime::ATOM) : null;

        return $request->getQueryParameterBag()->getStringFromParameter(
            $queryParameter,
            $defaultDateTimeString,
            function ($dateTimeString) use ($queryParameter) {
                $dateTime = DateTimeImmutable::createFromFormat(\DateTime::ATOM, $dateTimeString);

                if (!$dateTime) {
                    throw new \InvalidArgumentException(
                        "{$queryParameter} should be an ISO-8601 datetime, for example 2017-04-26T12:20:05+01:00"
                    );
                }

                return $dateTime;
            }
        );
    }


    /**
     * @param ParameterBagInterface $parameterBag
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
     * @param ParameterBagInterface $parameterBag
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

    /**
     * @param ParameterBagInterface $parameterBag
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
     * @param ParameterBagInterface $parameterBag
     * @return CalendarType[]
     */
    private function getCalendarTypesFromQuery(ParameterBagInterface $parameterBag)
    {
        return $parameterBag->getExplodedStringFromParameter(
            'calendarType',
            null,
            function ($calendarType) {
                return new CalendarType($calendarType);
            }
        );
    }

    /**
     * @param ParameterBagInterface $parameterBag
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
     * @param ParameterBagInterface $parameterBag
     * @param string $queryParameter
     * @return FacetName[]
     */
    private function getFacetsFromQuery(ParameterBagInterface $parameterBag, string $queryParameter): array
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            function ($value) {
                try {
                    return FacetName::fromNative(strtolower($value));
                } catch (\InvalidArgumentException $e) {
                    throw new \InvalidArgumentException("Unknown facet name '$value'.");
                }
            }
        );
    }
}
