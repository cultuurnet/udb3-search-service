<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class CalendarSummaryFormat
{
    private const ALLOWED_TYPES = ['text', 'html'];
    private const ALLOWED_FORMATS = ['xs', 'sm', 'md', 'lg'];

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $format;

    public function __construct(string $type, string $format)
    {
        if (!in_array($type, self::ALLOWED_TYPES)) {
            throw new UnsupportedParameterValue(
                'Invalid type: ' . $type . '. Use one of: ' . join(',', self::ALLOWED_TYPES)
            );
        }

        if (!in_array($format, self::ALLOWED_FORMATS)) {
            throw new UnsupportedParameterValue(
                'Invalid format: ' . $format . '. Use one of: ' . join(',', self::ALLOWED_FORMATS)
            );
        }

        $this->type = $type;
        $this->format = $format;
    }

    public static function fromCombinedParameter(string $parameter): self
    {
        [$format, $type] = explode('-', $parameter);
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
