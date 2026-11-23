<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use CultuurNet\UDB3\Search\MissingParameter;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final readonly class SearchText
{
    // Nobody types an address this long, and a longer text could exceed the Elasticsearch clause limit.
    private const MAX_LENGTH = 120;

    private string $text;

    /**
     * @var list<string>
     */
    private array $words;

    public function __construct(string $text)
    {
        $this->text = trim($text);

        if ($this->text === '') {
            throw new MissingParameter('Required "q" parameter is missing.');
        }
        if (mb_strlen($this->text) > self::MAX_LENGTH) {
            throw new UnsupportedParameterValue(
                sprintf('The "q" parameter can be at most %d characters long.', self::MAX_LENGTH)
            );
        }

        $this->words = preg_split('/\s+/u', $this->text) ?: [];
    }

    public function containsNumber(): bool
    {
        return preg_grep('/^\p{N}/u', $this->words) !== [];
    }

    public function endsWithNumber(): bool
    {
        // House numbers can carry letters (12A, 3bis), so only the first character counts.
        return preg_match('/^\p{N}/u', $this->words[array_key_last($this->words)] ?? '') === 1;
    }

    public function toString(): string
    {
        return $this->text;
    }
}
