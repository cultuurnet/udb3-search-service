<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Taxonomy;

use CultuurNet\UDB3\Search\Json;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

final class JsonTaxonomyApiClient implements TaxonomyApiClient
{
    private array $terms;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly string $termsEndpoint,
        private readonly LoggerInterface $logger
    ) {
        $request = new Request(
            'GET',
            $this->termsEndpoint,
        );

        $response = $this->client->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            $this->logger->error('Taxonomy Api returned a non-200 status code', [
                'status_code' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents(),
            ]);
            throw new TaxonomyApiProblem('Taxonomy Api returned a non-200 status code.');
        }
        $contents = $response->getBody()->getContents();
        if (empty($contents)) {
            $this->logger->error('Taxonomy Api returned no terms');
            throw new TaxonomyApiProblem('Taxonomy Api returned no terms.');
        }
        $contentsAsJson = Json::decodeAssociatively($contents);
        $this->terms = $contentsAsJson['terms'];
    }

    public function getTypes(): array
    {
        return $this->getTermsByDomain('eventtype');
    }

    public function getThemes(): array
    {
        return $this->getTermsByDomain('theme');
    }

    public function getFacilities(): array
    {
        return $this->getTermsByDomain('facility');
    }

    private function getTermsByDomain(string $domain): array
    {
        $termsByDomain = [];
        foreach ($this->terms as $term) {
            if ($term['domain'] === $domain) {
                $termsByDomain[$term['id']]['name'] = $term['name'];
            }
        }

        if (count($termsByDomain) === 0) {
            throw new TaxonomyApiProblem(
                sprintf('Could not find terms for Domain %s.', $domain)
            );
        }
        return $termsByDomain;
    }
}
