<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class CalendarSummaryFormat
{
    private const ALLOWED_TYPES = ['text', 'html'];
    private const ALLOWED_FORMATS = ['xs', 'sm', 'md', 'lg', 'xl'];

    private string $type;

    private string $format;

    public function __construct(string $type, string $format)
    {
        if (!in_array($type, self::ALLOWED_TYPES)) {
            throw new UnsupportedParameterValue(
                'Invalid type: ' . $type . '. Use one of: ' . implode(',', self::ALLOWED_TYPES)
            );
        }

        if (!in_array($format, self::ALLOWED_FORMATS)) {
            throw new UnsupportedParameterValue(
                'Invalid format: ' . $format . '. Use one of: ' . implode(',', self::ALLOWED_FORMATS)
            );
        }

        $this->type = $type;
        $this->format = $format;
    }

    public static function fromCombinedParameter(string $parameter): self
    {
        $parts = explode('-', $parameter);
        if (count($parts) !== 2) {
            throw new UnsupportedParameterValue(
                'Invalid calendar summary: ' . $parameter . '. Use the format {format}-{type}, e.g. md-text'
            );
        }

        [$format, $type] = $parts;
        return new self($type, $format);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
