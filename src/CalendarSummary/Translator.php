<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use InvalidArgumentException;

final class Translator
{
    private array $messages;

    public function __construct(private readonly string $locale)
    {
        $this->messages = [
            'en' => [
                'from' => 'from',
                'from_period' => 'from',
                'from_hour' => 'from',
                'till' => 'till',
                'till_included' => 'till',
                'till_hour' => 'till',
                'closed' => 'closed',
                'open_at' => 'open at',
                'open_at_consecutive_days' => 'open at',
                'at' => 'at',
                'open_every_day' => 'Open every day',
                'and' => 'and',
                'permanently_closed' => 'Permanently closed',
                'temporarily_closed' => 'Temporarily closed',
                'cancelled' => 'cancelled',
                'postponed' => 'postponed',
                'event_concluded' => 'Event ended',
                'sold_out' => 'Sold out or fully booked',
                'this' => 'This',
                'today' => 'Today',
                'tonight' => 'Tonight',
                'tomorrow' => 'Tomorrow',
                'to' => 'to',
                'open_every' => 'Open every',
                'open_every_end' => '',
                'except_during' => 'except during',
                'childcare' => 'childcare',
                'childcare_before' => 'early childcare',
                'childcare_after' => 'late childcare',
                'childcare_from' => 'from',
                'childcare_till' => 'till',
                'every_day' => 'every day',
                'every' => 'every',
                'overnight' => 'with overnight stay',
                'adjusted_hours_notice' => 'The hours may differ during this period.',
            ],
            'nl' => [
                'from' => 'van',
                'from_period' => 'vanaf',
                'from_hour' => 'van',
                'till' => 'tot',
                'till_included' => 'tot en met',
                'till_hour' => 'tot',
                'closed' => 'gesloten',
                'open_at' => 'open op',
                'open_at_consecutive_days' => 'open van',
                'at' => 'om',
                'open_every_day' => 'Alle dagen open',
                'and' => 'en',
                'permanently_closed' => 'Permanent gesloten',
                'temporarily_closed' => 'Tijdelijk gesloten',
                'cancelled' => 'geannuleerd',
                'postponed' => 'uitgesteld',
                'event_concluded' => 'Evenement afgelopen',
                'sold_out' => 'Volzet of uitverkocht',
                'this' => 'Deze',
                'today' => 'Vandaag',
                'tonight' => 'Vanavond',
                'tomorrow' => 'Morgen',
                'to' => 'tot en met',
                'open_every' => 'Elke',
                'open_every_end' => 'open',
                'except_during' => 'behalve tijdens',
                'childcare' => 'opvang',
                'childcare_before' => 'vooropvang',
                'childcare_after' => 'naopvang',
                'childcare_from' => 'vanaf',
                'childcare_till' => 'tot',
                'every_day' => 'elke dag',
                'every' => 'elke',
                'overnight' => 'met overnachting',
                'adjusted_hours_notice' => 'De uren kunnen afwijken tijdens deze periode.',
            ],
            'fr' => [
                'from' => 'du',
                'from_period' => 'du',
                'from_hour' => 'de',
                'till' => 'au',
                'till_included' => 'au',
                'till_hour' => 'à',
                'closed' => 'fermé',
                'open_at' => 'ouvert le',
                'open_at_consecutive_days' => 'ouvert le',
                'at' => 'à',
                'open_every_day' => 'Ouvert tous les jours',
                'and' => 'et',
                'permanently_closed' => 'Fermé définitivement',
                'temporarily_closed' => 'Fermé temporairement',
                'cancelled' => 'annulé',
                'postponed' => 'reporté',
                'event_concluded' => 'Fin de l\'événement',
                'sold_out' => 'Complet',
                'this' => 'Ce',
                'today' => 'Aujourd\'hui',
                'tonight' => 'Ce soir',
                'tomorrow' => 'Demain',
                'to' => 'au',
                'open_every' => 'Ouvert chaque',
                'open_every_end' => '',
                'except_during' => 'sauf pendant',
                'childcare' => 'garderie',
                'childcare_before' => 'garderie du matin',
                'childcare_after' => 'garderie du soir',
                'childcare_from' => 'dès',
                'childcare_till' => 'jusqu\'à',
                'every_day' => 'chaque jour',
                'every' => 'chaque',
                'overnight' => 'avec nuitée',
                'adjusted_hours_notice' => 'Les heures peuvent varier pendant cette période.',
            ],
            'de' => [
                'from' => 'von',
                'from_period' => 'aus',
                'from_hour' => 'von',
                'till' => 'bis',
                'till_included' => 'bis',
                'till_hour' => 'bis',
                'closed' => 'geschlossen',
                'open_at' => 'geöffnet',
                'open_at_consecutive_days' => 'geöffnet',
                'at' => 'um',
                'open_every_day' => 'Jeden Tag geöffnet',
                'and' => 'und',
                'permanently_closed' => 'Dauerhaft geschlossen',
                'temporarily_closed' => 'Vorübergehend geschlossen',
                'cancelled' => 'abgesagt',
                'postponed' => 'verschoben',
                'event_concluded' => 'Event abgeschlossen',
                'sold_out' => 'Ausgebucht oder ausverkauft',
                'this' => 'Diesen',
                'today' => 'Heute',
                'tonight' => 'Diesen Abend',
                'tomorrow' => 'Morgen',
                'to' => 'bis',
                'open_every' => 'Jeden',
                'open_every_end' => 'geöffnet',
                'except_during' => 'außer während',
                'childcare' => 'Kinderbetreuung',
                'childcare_before' => 'Frühbetreuung',
                'childcare_after' => 'Spätbetreuung',
                'childcare_from' => 'ab',
                'childcare_till' => 'bis',
                'every_day' => 'jeden Tag',
                'every' => 'jeden',
                'overnight' => 'mit Übernachtung',
                'adjusted_hours_notice' => 'Die Zeiten können in diesem Zeitraum abweichen.',
            ],
        ];

        // Fail on construction rather than on the first label, which would surface as an undefined index.
        if (!isset($this->messages[substr($locale, 0, 2)])) {
            throw new InvalidArgumentException('Unsupported locale: ' . $locale);
        }
    }

    public function translate(string $key): string
    {
        return $this->messages[$this->getLanguageCode()][$key];
    }

    /**
     * e.g. 'nl_BE'
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * e.g. 'nl'
     */
    public function getLanguageCode(): string
    {
        return substr($this->locale, 0, 2);
    }

    /**
     * German and English write their days of the week with a capital wherever they appear,
     * Dutch and French only at the start of a sentence.
     */
    public function capitalizesDaysOfWeek(): bool
    {
        return in_array($this->getLanguageCode(), ['de', 'en'], true);
    }
}
