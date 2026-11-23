# Calendar summary

Originally the `cultuurnet/calendar-summary-v3` library (inlined at v4.1.2).

Turns the calendar of an event or place from Uitdatabank into one human-readable line or block,
as HTML or plain text, in Dutch, French, German and English.

## How it works

You parse an `Offer` instance from JSONLD and ask a formatter for a size. Two things decide what
the summary looks like: the **calendar type** of the offer, and the **size** you ask for.

### Calendar types

The `calendarType` property follows from the dates and opening hours the organizer entered, so it
is not chosen directly.

| Type | The offer has | Example |
| --- | --- | --- |
| `single` | one date, with a start and an end hour | a concert on 28 November |
| `multiple` | several dates, each with its own hours | a course of six evenings |
| `periodic` | opening hours per day of the week, within a start and end date | an exhibition, open Tuesday to Sunday until June |
| `permanent` | opening hours per day of the week, without an end | a museum |

### Sizes

| Size | Shows | Use it for |
| --- | --- | --- |
| `xs` | the date, as short as possible | mobile apps, tight lists |
| `sm` | the date, a little longer | lists |
| `md` | the date and the hours | search results |
| `lg` | everything of `md`, plus the opening hours per day, the childcare, the overnight stay, and a warning when the hours differ during some periods | a detail page |
| `xl` | everything of `lg`, plus the adjusted days and the closed days themselves | a detail page of an offer that has them |

`xl` only differs from `lg` for `periodic` and `permanent`, because only those have opening hours,
adjusted days and closed days. For `single` and `multiple` an `xl` request returns the `lg` summary.

Using any other format will throw an exception.

### Fields that end up in a summary

| Field | Types that have it | Where it shows |
| --- | --- | --- |
| `startDate`, `endDate` | all | every size |
| `subEvent` | `multiple` | `xs` and up, one line per date |
| `openingHours` | `periodic`, `permanent` | `md` and up |
| `openingHours[].childcare` | `periodic`, `permanent` | `lg` and `xl` |
| `subEvent[].childcare` | `single`, `multiple` | `lg` and `xl` |
| `subEvent[].overnight` | `single`, `multiple` | `lg` and `xl` |
| `openingHoursAdjustedDays` | `periodic`, `permanent` | `xl` lists them, `lg` only warns they exist |
| `openingHoursClosedDays` | `periodic`, `permanent` | `xl` only |
| `status` | all | every size, as `(geannuleerd)` or `(uitgesteld)` |
| `bookingAvailability` | all | every size, as `(volzet of uitverkocht)` |

Childcare and the overnight stay only show when the offer really has them: an offer without
childcare says nothing about childcare.

## Endpoint

`GET /calendar-summary/{events|places}/{id}` returns the summary of one event or place, read from
the original JSON-LD in the search index. It is meant to back Entry API's
`/{events|places}/{id}/calendar-summary`. Like every Search API route it needs an API key or client id.

| Parameter | Values | Default |
| --- | --- | --- |
| `language` | `nl`, `fr`, `de`, `en` | `nl` |
| `size` | `xs`, `sm`, `md`, `lg`, `xl` | `lg` |
| `style` | `text`, `html` | `text` |
| `hidePast` | `true`, `false` | `false` |
| `timezone` | any [PHP timezone](http://php.net/manual/en/timezones.php) | `Europe/Brussels` |

The response is `text/plain` or `text/html`, depending on `style`. The languages use the same locales
as the `calendarSummary` embedded in search results, so both render a language identically.

An unknown `timezone` falls back to `Europe/Brussels` instead of being refused, because Entry API
never validated it and existing integrations may still send one. An id that is not an event or
place of the requested type returns a 404.

Entry API's deprecated aliases (`langCode`, `format`, `timeZone` and the `Accept` header) are not
supported here. Entry API is expected to map them when it calls this endpoint.

## Embedded in search results

`embedCalendarSummaries[]` on `/offers`, `/events` and `/places` embeds summaries in each result, for
example `embedCalendarSummaries[]=md-text`. They use `Europe/Brussels` and hide past dates. Add
`embedCalendarSummariesHidePast=false` to include past dates, like Entry API's event exports do.

## Formatter parameters

There are 3 (optional) parameters which can be used on the initialisation of the formatters.

### langCode

(string) Default value: `'nl_BE'`.
Changes the language of the output. Works in nl, fr, de and en. The format here is standard PHP
locales, for example `'fr_BE'` or `'de_BE'`.

### hidePastDates

(boolean) Default value: `false`.
Only used on offers with a calendarType `multiple`. When true, dates in the past won't be in the
formatter's output.

### timeZone

(string) Default value: `'Europe/Brussels'`.
Supported timezones can be found in this [list](http://php.net/manual/en/timezones.php).

## Example

```php
<?php

// Make sure to either deserialize the Event/Place from JSON, or set the necessary properties through setCalendarType() etc.
$offer = \CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer::fromJsonLd('JSONLD_STRING');

// This will format the calendar info of `$offer` in a medium HTML output
$calendarHTML = new \CultuurNet\UDB3\Search\CalendarSummary\CalendarHTMLFormatter('nl_BE', true, 'Europe/Brussels');
$calendarHTML->format($offer, 'md');

// This will format the calendar info of `$offer` in a large plain text output
$calendarPlainText = new \CultuurNet\UDB3\Search\CalendarSummary\CalendarPlainTextFormatter('fr_BE', true, 'Europe/Paris');
$calendarPlainText->format($offer, 'lg');
```

## Examples of the output

Every combination of type, size and format has at least one example file under
`tests/CalendarSummary/<Type>/data/<Formatter>/<what-it-shows>.html` or `.txt`. Each file is the exact output and
nothing else, and its name says what it shows, so the folders double as documentation. For example:

- [`tests/CalendarSummary/Single/data/LargeSingleHTMLFormatter/`](../../tests/CalendarSummary/Single/data/LargeSingleHTMLFormatter) — a
  single date at `lg`, with and without childcare and overnight stay
- [`tests/CalendarSummary/Periodic/data/ExtraLargePeriodicPlainTextFormatter/everything-at-once.txt`](../../tests/CalendarSummary/Periodic/data/ExtraLargePeriodicPlainTextFormatter/everything-at-once.txt)
  — the longest summary the library can produce

The same folders also hold `-in-english`, `-in-french` and `-in-german` variants. Those record what
those languages produce *today*, wrong wording included, so the fixes tracked in
[III-7462](https://jira.publiq.be/browse/III-7462) show up as a diff. Read the Dutch files for the
wording as it is meant to be.

## Contributing

The rules every summary follows — braces, capitals, joining, order, the HTML classes, and where a
new field goes — are in [formatting-rules.md](formatting-rules.md).
