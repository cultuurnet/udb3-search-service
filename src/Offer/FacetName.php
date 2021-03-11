<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use InvalidArgumentException;

final class FacetName
{
    private const REGIONS = 'regions';
    private const TYPES = 'types';
    private const THEMES = 'themes';
    private const FACILITIES = 'facilities';
    private const LABELS = 'labels';

    private static $allowedValues = [
        self::REGIONS,
        self::TYPES,
        self::THEMES,
        self::FACILITIES,
        self::LABELS,
    ];

    /**
     * @var string
     */
    private $facetName;

    public function __construct(string $facetName)
    {
        if (!in_array($facetName, self::$allowedValues)) {
            throw new InvalidArgumentException(
                'Invalid FacetName: ' . $facetName . '. Should be one of ' . implode(', ', self::$allowedValues)
            );
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
}
