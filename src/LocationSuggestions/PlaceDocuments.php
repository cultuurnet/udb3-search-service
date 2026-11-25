<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use function Symfony\Component\String\u;

/**
 * Turns the JSON-LD of a UiTdatabank place into a location suggestion document.
 */
final readonly class PlaceDocuments
{
    // The same places Search API returns by default.
    private const PUBLIC_WORKFLOW_STATUSES = ['APPROVED', 'READY_FOR_VALIDATION'];

    public function __construct(private MunicipalityRegions $municipalityRegions)
    {
    }

    public static function id(string $placeId): string
    {
        return 'place:' . $placeId;
    }

    /**
     * @param array<string, mixed> $place
     * @return array<string, mixed>|null Null when the place is not suggested.
     */
    public function create(array $place): ?array
    {
        if (!in_array($place['workflowStatus'] ?? null, self::PUBLIC_WORKFLOW_STATUSES, true)) {
            return null;
        }

        // Without coordinates there is nothing to filter on.
        $latitude = $place['geo']['latitude'] ?? null;
        $longitude = $place['geo']['longitude'] ?? null;
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        $languages = $this->languages($place);
        $names = $this->unique(array_map(
            fn (string $language): string => $this->clean($place['name'][$language] ?? ''),
            $languages
        ));
        if ($names === []) {
            return null;
        }

        $addresses = array_values(array_filter(array_map(
            static fn (string $language): ?array => $place['address'][$language] ?? null,
            $languages
        )));
        // The addresses come from Belgium only, so a place abroad would be the only suggestion outside it.
        if ($addresses === [] || ($addresses[0]['addressCountry'] ?? '') !== 'BE') {
            return null;
        }

        $addressWords = [];
        foreach ($addresses as $address) {
            $addressWords[] = $this->clean($address['streetAddress'] ?? '');
            $addressWords[] = $this->clean($address['postalCode'] ?? '');
            $locality = $this->clean($address['addressLocality'] ?? '');
            $addressWords[] = $locality;
            // A street has its municipality from the address register. A place only has the locality, so Café X in Kessel-Lo
            // would not be found on "café x leuven" without this.
            array_push($addressWords, ...$this->municipalityRegions->findMunicipalityNamesOfSubMunicipality($locality));
        }

        return [
            'type' => 'place',
            'label' => sprintf('%s (%s)', $names[0], $this->address($addresses[0])),
            'name' => $names,
            'search_text' => implode(' ', $this->unique([...$names, ...$addressWords])),
            'location' => ['lat' => (float) $latitude, 'lon' => (float) $longitude],
        ];
    }

    /**
     * @param array<string, mixed> $place
     * @return list<string> The main language first, then the other languages of the name.
     */
    private function languages(array $place): array
    {
        $mainLanguage = is_string($place['mainLanguage'] ?? null) ? $place['mainLanguage'] : 'nl';
        $languages = [$mainLanguage, ...array_keys(is_array($place['name'] ?? null) ? $place['name'] : [])];

        return array_values(array_unique(array_map('strval', $languages)));
    }

    /**
     * @param array<string, mixed> $address
     */
    private function address(array $address): string
    {
        $locality = trim($this->clean($address['postalCode'] ?? '') . ' ' . $this->clean($address['addressLocality'] ?? ''));
        $street = $this->clean($address['streetAddress'] ?? '');

        return $street === '' ? $locality : $street . ', ' . $locality;
    }

    private function clean(mixed $text): string
    {
        // Names and addresses are typed by hand, sometimes with line breaks and runs of spaces.
        return is_string($text) ? trim((string) preg_replace('/\s+/u', ' ', $text)) : '';
    }

    /**
     * @param list<string> $words
     * @return list<string>
     */
    private function unique(array $words): array
    {
        $unique = [];
        foreach ($words as $word) {
            if ($word !== '') {
                // Folded like the location_analyzer does, so Liège and LIEGE end up as one word.
                $unique[u($word)->ascii()->lower()->toString()] ??= $word;
            }
        }

        return array_values($unique);
    }
}
