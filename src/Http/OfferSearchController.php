<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferQueryBuilder;
use CultuurNet\UDB3\Search\Http\Authentication\Consumer;
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
    private OfferQueryBuilderInterface $queryBuilder;

    private OfferRequestParserInterface $requestParser;

    private OfferSearchServiceInterface $searchService;

    private string $regionIndexName;

    private string $regionDocumentType;

    private QueryStringFactory $queryStringFactory;

    private FacetTreeNormalizerInterface $facetTreeNormalizer;

    private OfferSupportedParameters $offerParameterWhiteList;

    private Consumer $consumer;

    public function __construct(
        OfferQueryBuilderInterface $queryBuilder,
        OfferRequestParserInterface $offerRequestParser,
        OfferSearchServiceInterface $searchService,
        string $regionIndexName,
        string $regionDocumentType,
        QueryStringFactory $queryStringFactory,
        FacetTreeNormalizerInterface $facetTreeNormalizer,
        Consumer $consumer
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->requestParser = $offerRequestParser;
        $this->searchService = $searchService;
        $this->regionIndexName = $regionIndexName;
        $this->regionDocumentType = $regionDocumentType;
        $this->queryStringFactory = $queryStringFactory;
        $this->facetTreeNormalizer = $facetTreeNormalizer;
        $this->offerParameterWhiteList = new OfferSupportedParameters();
        $this->consumer = $consumer;
    }

    public function __invoke(ApiRequest $request): ResponseInterface
    {
        $this->offerParameterWhiteList->guardAgainstUnsupportedParameters(
            $request->getQueryParamsKeys()
        );

        $start = new Start((int) $request->getQueryParam('start', 0));
        $limit = new Limit((int) $request->getQueryParam('limit', 30));

        $queryBuilder = $this->queryBuilder->withStartAndLimit($start, $limit);

        if ($this->consumer->getId() && $queryBuilder instanceof ElasticSearchOfferQueryBuilder) {
            $queryBuilder = $queryBuilder->withShardPreference('consumer_' . $this->consumer->getId());
        }

        $queryBuilder = $this->requestParser->parse($request, $queryBuilder);

        $parameterBag = $request->getQueryParameterBag();

        $textLanguages = $this->getLanguagesFromQuery($parameterBag, 'textLanguages');

        if ($this->consumer->getDefaultQuery()) {
            $queryBuilder = $queryBuilder->withAdvancedQuery(
                $this->queryStringFactory->fromString($this->consumer->getDefaultQuery()),
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

        if ($request->hasQueryParam('recommendationFor')) {
            $queryBuilder = $queryBuilder->withRecommendationForFilter(
                $request->getQueryParam('recommendationFor')
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
        if ($country instanceof Country) {
            $queryBuilder = $queryBuilder->withAddressCountryFilter($country);
        }

        $audienceType = $this->getAudienceTypeFromQuery($parameterBag);
        if ($audienceType instanceof AudienceType) {
            $queryBuilder = $queryBuilder->withAudienceTypeFilter($audienceType);
        }

        $price = $request->getQueryParam('price');
        $minPrice = $request->getQueryParam('minPrice');
        $maxPrice = $request->getQueryParam('maxPrice');

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

        $includeVideos = $parameterBag->getBooleanFromParameter('hasVideos');
        if (!is_null($includeVideos)) {
            $queryBuilder = $queryBuilder->withVideosFilter($includeVideos);
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
            static fn (string $parameter): CalendarSummaryFormat => CalendarSummaryFormat::fromCombinedParameter($parameter),
            $parameterBag->getArrayFromParameter('embedCalendarSummaries')
        );

        $resultTransformer = ResultTransformerFactory::create(
            (bool) $parameterBag->getBooleanFromParameter('embed'),
            (bool) $parameterBag->getBooleanFromParameter('embedUitpasPrices'),
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
     * @return TermId[]
     */
    private function getTermIdsFromQuery(ParameterBagInterface $parameterBag, string $queryParameter): array
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            fn ($value): TermId => new TermId($value)
        );
    }

    /**
     * @return TermLabel[]
     */
    private function getTermLabelsFromQuery(ParameterBagInterface $parameterBag, string $queryParameter): array
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            fn ($value): TermLabel => new TermLabel($value)
        );
    }

    /**
     * @return LabelName[]
     */
    private function getLabelsFromQuery(ParameterBagInterface $parameterBag, string $queryParameter): array
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            fn ($value): LabelName => new LabelName($value)
        );
    }

    /**
     * @return Language[]
     */
    private function getLanguagesFromQuery(ParameterBagInterface $parameterBag, string $queryParameter): array
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            fn ($value): Language => new Language($value)
        );
    }

    /**
     * @return RegionId[]
     */
    private function getRegionIdsFromQuery(ParameterBagInterface $parameterBag, string $queryParameter): array
    {
        return $parameterBag->getArrayFromParameter(
            $queryParameter,
            fn ($value): RegionId => new RegionId($value)
        );
    }

    private function getAudienceTypeFromQuery(ParameterBagInterface $parameterBag): ?AudienceType
    {
        return $parameterBag->getStringFromParameter(
            'audienceType',
            'everyone',
            fn ($audienceType): AudienceType => new AudienceType($audienceType)
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
