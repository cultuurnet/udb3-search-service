<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer;

use CultuurNet\UDB3\Search\Http\Parameters\ParameterBagInterface;
use DateTimeImmutable;

/**
 * Reads the birthdateRangeFrom/birthdateRangeTo query parameters as parsed date
 * lists, shared between BirthdateRangeOfferRequestParser (which builds the query
 * filter and rejects an invalid from/to combination) and
 * MatchingBirthdateRangesResolver (which reports matches and degrades leniently
 * on the same combination instead).
 */
final class BirthdateRangeDateParameters
{
    /** @var DateTimeImmutable[] */
    private array $fromDates;

    /** @var DateTimeImmutable[] */
    private array $toDates;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->fromDates = $parameterBag->getExplodedDateFromParameter('birthdateRangeFrom');
        $this->toDates = $parameterBag->getExplodedDateFromParameter('birthdateRangeTo');
    }

    /**
     * @return DateTimeImmutable[]
     */
    public function getFromDates(): array
    {
        return $this->fromDates;
    }

    /**
     * @return DateTimeImmutable[]
     */
    public function getToDates(): array
    {
        return $this->toDates;
    }

    public function hasMatchingCounts(): bool
    {
        return count($this->fromDates) === count($this->toDates);
    }
}
