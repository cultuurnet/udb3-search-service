<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\CalendarHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\CalendarPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchRequestFailed;
use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

final readonly class CalendarSummaryController
{
    public function __construct(
        private ElasticSearchClient $client,
        private string $eventIndexName,
        private string $placeIndexName,
    ) {
    }

    /**
     * @param array{offerType: string, offerId: string} $args
     */
    public function __invoke(ApiRequestInterface $request, array $args): ResponseInterface
    {
        $parameters = new CalendarSummaryParameters($request);
        $offer = Offer::fromJsonLd($this->fetchJsonLd($args['offerType'], $args['offerId']));

        $type = $parameters->format->getType();
        $formatter = $type === 'html'
            ? new CalendarHTMLFormatter($parameters->locale, $parameters->hidePast, $parameters->timezone)
            : new CalendarPlainTextFormatter($parameters->locale, $parameters->hidePast, $parameters->timezone);

        $summary = trim($formatter->format($offer, $parameters->format->getFormat()));

        return $type === 'html' ? ResponseFactory::html($summary) : ResponseFactory::text($summary);
    }

    private function fetchJsonLd(string $offerType, string $offerId): string
    {
        try {
            $response = $this->client->get([
                'index' => $offerType === 'places' ? $this->placeIndexName : $this->eventIndexName,
                'id' => $offerId,
                '_source' => ['@type', 'originalEncodedJsonLd'],
            ]);
        } catch (ElasticSearchRequestFailed $exception) {
            if ($exception->isNotFound()) {
                throw new OfferNotFound($offerType, $offerId);
            }
            throw $exception;
        }

        if (strtolower($response['_source']['@type']) . 's' !== $offerType) {
            throw new OfferNotFound($offerType, $offerId);
        }

        return $response['_source']['originalEncodedJsonLd'];
    }
}
