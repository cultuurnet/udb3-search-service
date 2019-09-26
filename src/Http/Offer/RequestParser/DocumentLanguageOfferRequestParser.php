<?php

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Http\Parameters\SymfonyParameterBagAdapter;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class DocumentLanguageOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface$request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = new SymfonyParameterBagAdapter(new ParameterBag($request->getQueryParams()));

        $languageCallback = function ($value) {
            return new Language($value);
        };

        // Add mainLanguage parameter as a filter.
        $mainLanguage = $parameterBagReader->getStringFromParameter('mainLanguage', null, $languageCallback);
        if ($mainLanguage) {
            $offerQueryBuilder = $offerQueryBuilder->withMainLanguageFilter($mainLanguage);
        }

        // Add languages parameter(s) as filter(s).
        $languages = $parameterBagReader->getArrayFromParameter('languages', $languageCallback);
        foreach ($languages as $language) {
            $offerQueryBuilder = $offerQueryBuilder->withLanguageFilter($language);
        }

        // Add completedLanguages parameter(s) as filter(s).
        $completedLanguages = $parameterBagReader->getArrayFromParameter('completedLanguages', $languageCallback);
        foreach ($completedLanguages as $completedLanguage) {
            $offerQueryBuilder = $offerQueryBuilder->withCompletedLanguageFilter($completedLanguage);
        }

        return $offerQueryBuilder;
    }
}
