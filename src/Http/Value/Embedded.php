<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Value;

class Embedded
{
    /**
     * @var bool $value
     */
    private $value = false;
    
    /**
     * Embedded constructor.
     */
    private function __construct()
    {
    }
    
    public static function create(?string $value): Embedded
    {
        $instance = new self();
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $instance->value = true;
        }
        return $instance;
    }
    
    public function isTrue(): bool
    {
        return $this->value === true;
    }
    
    public function isFalse(): bool
    {
        return $this->value === false;
    }
}
