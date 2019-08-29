<?php
declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Language;

class Language
{
    protected $code;

    public function __construct(string $code)
    {
        if (!preg_match('/^[a-z]{2}$/', $code)) {
            throw new \InvalidArgumentException(
                'Invalid language code: ' . $code
            );
        }
        $this->code = $code;
    }

    public function __toString(): string
    {
        return $this->code;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
