<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use InvalidArgumentException;

final class FacetName
{
    public const REGIONS = 'regions';
    public const TYPES = 'types';
    public const THEMES = 'themes';
    public const FACILITIES = 'facilities';
    public const LABELS = 'labels';

    /**
     * @var string
     */
    private $facetName;

    public function __construct(string $facetName)
    {
        if (!in_array($facetName, $this->getAllowedValues())) {
            throw new InvalidArgumentException('The given facet name ' . $facetName . ' is not supported');
        }

        $this->facetName = $facetName;
    }

    public static function regions(): self
    {
        return new self(self::REGIONS);
    }

    public static function types(): self
    {
        return new self(self::TYPES);
    }

    public static function themes(): self
    {
        return new self(self::THEMES);
    }

    public static function facilities(): self
    {
        return new self(self::FACILITIES);
    }

    public static function labels(): self
    {
        return new self(self::LABELS);
    }

    public function toString(): string
    {
        return $this->facetName;
    }

    public function sameValueAs(FacetName $otherFacetName): bool
    {
        return $this->toString() === $otherFacetName->toString();
    }

    private function getAllowedValues(): array
    {
        return [
            self::REGIONS,
            self::TYPES,
            self::THEMES,
            self::FACILITIES,
            self::LABELS,
        ];
    }
}
