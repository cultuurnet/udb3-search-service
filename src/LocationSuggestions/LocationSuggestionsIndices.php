<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

/**
 * The names of the location suggestion indices. They only matter to this code, so they are not configurable.
 * The read alias, which the endpoint searches, can span more than one kind of index.
 */
final readonly class LocationSuggestionsIndices
{
    // Rebuilt by every address import, which only replaces and deletes indices with this prefix.
    public const ADDRESSES_PREFIX = 'location_suggestions_addresses_v';
}
