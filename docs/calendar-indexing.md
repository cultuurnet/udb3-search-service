# Offer Availability Indexing

This document describes the calendar in JSON-LD format and how its fields are indexed in Elasticsearch before being exposed through search parameters.

---

## Calendar types

Every offer has a `calendarType`. The type determines what date information is stored in JSON-LD and how it gets indexed.

### Events

Events support four types.

| Type | What it means                                                                    |
|---|----------------------------------------------------------------------------------|
| `single` | Happens once, on a fixed single period with a start datetime and an end datetime |
| `multiple` | Happens on several fixed periods, each with their own start and end datetime     |
| `periodic` | Runs across a date range, with optional opening hours |
| `permanent` | No fixed period, with optional opening hours |

**single**

```json
{
  "calendarType": "single",
  "startDate": "2024-06-01T10:00:00+00:00",
  "endDate": "2024-06-01T18:00:00+00:00"
}
```

**multiple**

```json
{
  "calendarType": "multiple",
  "startDate": "2024-04-30T00:00:00+00:00",
  "endDate": "2024-05-07T00:00:00+00:00",
  "subEvent": [
    { "startDate": "2024-04-30T10:00:00+00:00", "endDate": "2024-04-30T12:00:00+00:00" },
    { "startDate": "2024-05-01T10:00:00+00:00", "endDate": "2024-05-01T12:00:00+00:00" },
    { "startDate": "2024-05-07T10:00:00+00:00", "endDate": "2024-05-07T12:00:00+00:00" }
  ]
}
```

**periodic**

```json
{
  "calendarType": "periodic",
  "startDate": "2024-06-01T00:00:00+00:00",
  "endDate": "2024-08-31T23:59:59+00:00",
  "openingHours": [
    { "dayOfWeek": ["monday", "wednesday", "friday"], "opens": "08:30", "closes": "17:00" }
  ]
}
```

**permanent**

```json
{
  "calendarType": "permanent",
  "openingHours": [
    { "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"], "opens": "09:00", "closes": "17:00" },
    { "dayOfWeek": ["saturday"], "opens": "10:00", "closes": "14:00" }
  ]
}
```

### Places

Places only support `periodic` and `permanent`. They are physical locations, not one-time occurrences, so `single` and `multiple` do not apply.

| Type | What it means |
|---|---|
| `periodic` | Open during a date range, with optional opening hours |
| `permanent` | Always open (no fixed end date), with optional opening hours |

The JSON-LD shape is the same as for events.

---

## Source fields vs. indexed fields

Not every field in the JSON-LD ends up in Elasticsearch. Some fields are **read at index time and then discarded**. Others are **stored and queryable**.

| Role | Fields | Stored in ES? |
|---|---|---|
| Source only | `calendarType`, `startDate`, `endDate`, `openingHours`, `childcare` | No, consumed to build the indexed fields below |
| Indexed | `dateRange`, `localTimeRange`, `subEvent[]`, `availableRange`, `hasChildcare`, `dayOfWeekHits` | Yes, queryable |

`openingHours` is a good example of a source field: it is never stored and never queryable directly. The indexer reads it, expands it into `subEvent[]` entries, and those entries become the queryable surface.

`startDate` and `endDate` work the same way. They are read to build the top-level `dateRange` field and then discarded.

---

## Indexing

### Top-level date range

Every offer gets a top-level `dateRange` field. It spans from the earliest start datetime to the latest end datetime across all sub-events. This makes simple datetime queries fast. No need to look inside nested sub-events.

A `localTimeRange` is also stored. It holds only the time-of-day part (no date), which enables queries like "open between 14:00 and 18:00."

Both fields sit directly on the document, not inside the `subEvent[]` array:

```json
{
  "dateRange": {
    "gte": "2024-06-01T10:00:00+00:00",
    "lte": "2024-08-31T17:00:00+00:00"
  },
  "localTimeRange": {
    "gte": "08:30",
    "lte": "17:00"
  },
  "subEvent": [ ... ]
}
```

### Sub-event indexing

Every offer also gets a `subEvent[]` array. Each entry represents one time slot. The indexer expands the source calendar into this array.

`subEvent` is mapped as a `nested` field, and Elasticsearch rejects an entire document once it exceeds
`index.mapping.nested_objects.limit` (default 10,000 nested objects per document, across all nested fields
combined). Calendars with a large or unbounded `openingHours`-driven expansion (e.g. `periodic` with a very
long `startDate`–`endDate` range) can generate more sub-events than that. To stay safely under the limit,
`SubEventCapTransformer` truncates `subEvent` to the first N entries encountered and logs a warning when
truncation happens. The cap is `SubEventCapTransformer::DEFAULT_CAP` (9,900) — the same for every
environment, so it's a code constant rather than a deployment-level config value; change it there if the
Elasticsearch index setting ever changes. Only the `subEvent[]` array is capped, `dateRange` and
`localTimeRange` are still built from the full, uncapped list.

**Expanding rules:**

| Calendar type | Opening hours | Result |
|---|---|---|
| `single` | n/a | 1 sub-event (start → end) |
| `multiple` | n/a | One sub-event per explicit entry |
| `periodic` | No | 1 sub-event covering the whole range |
| `periodic` | Yes | One sub-event per day-of-week × time slot within the range |
| `permanent` | No | 1 open-ended sub-event |
| `permanent` | Yes | One sub-event per day-of-week × time slot, from −6 months to +12 months |

Each sub-event in Elasticsearch looks like this:

```json
{
  "dateRange": {
    "gte": "2024-06-03T08:30:00+00:00",
    "lte": "2024-06-03T17:00:00+00:00"
  },
  "localTimeRange": {
    "gte": "08:30",
    "lte": "17:00"
  },
  "status": "Available",
  "bookingAvailability": "Available"
}
```

**Example: periodic with opening hours**

JSON-LD Input:
```json
{
  "calendarType": "periodic",
  "startDate": "2024-06-03T00:00:00+00:00",
  "endDate": "2024-06-07T23:59:59+00:00",
  "openingHours": [
    { "dayOfWeek": ["monday", "wednesday"], "opens": "08:30", "closes": "09:17" }
  ]
}
```

The range covers Monday 3 June to Friday 7 June. The opening hours apply on Monday and Wednesday, so the indexer produces two sub-events:

```json
{
  "subEvent": [
    {
      "dateRange": { "gte": "2024-06-03T08:30:00+00:00", "lte": "2024-06-03T09:17:00+00:00" },
      "localTimeRange": { "gte": "08:30", "lte": "09:17" },
      "status": "Available",
      "bookingAvailability": "Available"
    },
    {
      "dateRange": { "gte": "2024-06-05T08:30:00+00:00", "lte": "2024-06-05T09:17:00+00:00" },
      "localTimeRange": { "gte": "08:30", "lte": "09:17" },
      "status": "Available",
      "bookingAvailability": "Available"
    }
  ]
}
```

### Permanent offers and the rolling window

Permanent offers with opening hours are indexed with a rolling window of **−6 months to +12 months** from the moment of indexing. The indexer calculates this window relative to the current date and generates one sub-event per day-of-week × time slot within that range.

This means the indexed sub-events become stale over time. To keep the window current, the `udb3-core:reindex-permanent` console command re-indexes all permanent offers. It scrolls through all permanent offers in Elasticsearch and re-runs the full indexing pipeline for each one, recalculating the window based on the current date.

There is no built-in schedule. Running this command periodically (e.g. via a cron job) is the responsibility of the infrastructure.

---

## Search parameters

### Top-level queries

When you filter on datetime, local time, status, or booking availability **on their own**, the query hits the top-level fields.

| Parameter(s) | ES field |
|---|---|
| `dateFrom`, `dateTo` | `dateRange` |
| `localTimeFrom`, `localTimeTo` | `localTimeRange` |
| `status` | `status` |
| `bookingAvailability` | `bookingAvailability` |
| `availableFrom`, `availableTo` | `availableRange` |

`availableRange` is always a top-level filter. It controls the publication window: when the offer is publicly visible. It is separate from when the offer actually takes place.

**Example: events happening on a specific day:**
```
GET /offers?dateFrom=2024-06-01T00:00:00+00:00&dateTo=2024-06-01T23:59:59+00:00
```
→ runs a range query on the top-level `dateRange` field.

**Example: events with status "Unavailable":**
```
GET /offers?status=Unavailable
```
→ runs a term query on the top-level `status` field.

### Nested queries (sub-event level)

As soon as you combine **two or more** of `date*`, `localTime*`, `status`, or `bookingAvailability`, the query switches to a nested query against `subEvent[]`.

**Why?** A top-level query could match by accident. Imagine an event with two sub-events: sub-event A is on 1 June but cancelled, sub-event B is available but on 8 June. A top-level query for "1 June AND available" would match this event, even though no single sub-event actually meets both conditions. A nested query fixes this by requiring all conditions to apply to the **same** sub-event.

| Parameter(s) in the combination | Nested ES field |
|---|---|
| `dateFrom`, `dateTo` | `subEvent.dateRange` |
| `localTimeFrom`, `localTimeTo` | `subEvent.localTimeRange` |
| `status` | `subEvent.status` |
| `bookingAvailability` | `subEvent.bookingAvailability` |

**Example: available events on a specific day:**
```
GET /offers?dateFrom=2024-06-01T00:00:00+00:00&dateTo=2024-06-01T23:59:59+00:00&status=Available
```
→ combines `date*` and `status`, so the query runs as a nested query on `subEvent[]`. Both conditions must hold on the same sub-event.

**Example: events bookable on a specific afternoon:**
```
GET /offers?localTimeFrom=14:00&localTimeTo=18:00&bookingAvailability=Available
```
→ combines `localTime*` and `bookingAvailability`, so again a nested query.

---

## Childcare

Sub-events (`single`, `multiple`) and opening hours (`periodic`, `permanent`) may carry an
optional `childcare` range in the source JSON-LD. It describes childcare offered before/after the
activity:

```json
{
  "calendarType": "multiple",
  "subEvent": [
    {
      "startDate": "2024-06-01T10:00:00+00:00",
      "endDate": "2024-06-01T12:00:00+00:00",
      "childcare": { "start": "09:00", "end": "13:00" }
    }
  ]
}
```

```json
{
  "calendarType": "periodic",
  "startDate": "2024-06-01T00:00:00+00:00",
  "endDate": "2024-08-31T23:59:59+00:00",
  "openingHours": [
    { "dayOfWeek": ["monday"], "opens": "08:30", "closes": "17:00", "childcare": { "start": "08:00", "end": "18:00" } }
  ]
}
```

### Childcare must not influence the effective time

Childcare hours relate to a service around the activity, not to the activity itself. They must
**not** extend or shift `dateRange`, `localTimeRange`, or the generated `subEvent[]`. The `childcare`
range is therefore a source-only field: it is never expanded into sub-events and never widens any
range.

### Indexing

Instead, the indexer sets a single top-level boolean, `hasChildcare`:

```json
{ "hasChildcare": true }
```

It is `true` when at least one source sub-event or opening hour has a `childcare` range configured,
and `false` otherwise. Like `status` and `bookingAvailability`, it is always present on every
document (defaulting to `false`), so a `term` filter is reliable. Childcare is event-only today;
place documents always index `hasChildcare: false`.

### Search parameter

| Parameter | ES field | Behaviour |
|---|---|---|
| `hasChildcare=true` | `hasChildcare` | Only offers that have childcare on at least one sub-event or opening hour. |
| `hasChildcare=false` | `hasChildcare` | Only offers without any childcare configured. |
| _(omitted)_ | — | No childcare filtering; behaviour unchanged. |

```
GET /offers?hasChildcare=true
```
→ runs a `term` query on the top-level `hasChildcare` field. It is independent of `dateRange` and
the other calendar filters.

---

## Day of week

`periodic` and `permanent` offers with opening hours recur on fixed weekdays. To support "find offers
that occur on a given weekday" queries, the indexer stores how often each weekday actually occurs.

### Indexing

The indexer sets a single top-level object, `dayOfWeekHits`, holding an **occurrence count per weekday**:

```json
{
  "dayOfWeekHits": {
    "monday": 26,
    "tuesday": 26,
    "wednesday": 26,
    "thursday": 26,
    "friday": 26,
    "saturday": 25,
    "sunday": 0
  }
}
```

It is a plain `object`, so Elasticsearch flattens it into independent integer fields
(`dayOfWeekHits.monday`, …) that are queried and cached independently, with no nested-query join cost.

Like `hasChildcare`, every document always carries all seven sub-fields (defaulting to `0`), so a
range filter on any weekday is reliable. Calendar types that have no occurrences to count (`single`,
`periodic`/`permanent` without opening hours) index all-zero counts.

**Computation:**

| Calendar type | Opening hours | `dayOfWeekHits` |
|---|---|---|
| `single` | n/a | all zero |
| `multiple` | n/a | occurrence days per weekday, from the explicit `subEvent[]` |
| `periodic` | No | all zero |
| `periodic` | Yes | occurrences per weekday within `startDate`–`endDate` |
| `permanent` | No | all zero |
| `permanent` | Yes | occurrences per weekday within the −6/+12 month rolling window |

For `periodic`/`permanent`, the count comes from `EffectiveOpeningHours::dayCounts()`, the same single
calendar walk that builds `subEvent[]` (via `EffectiveOpeningHoursResolver::resolve()`), so the counts
are consistent with the generated sub-events by construction. For `multiple`, the count is derived
directly from the explicit source `subEvent[]` (there are no opening hours to resolve), taking each
sub-event's start date in the offer's local timezone.

A count is always **days, not slots**: a weekday with two opening-hour slots — or two `multiple`
sub-events — on the same date counts once.

Because the count is derived from the effective (closures-applied) opening hours, it becomes stale the
same way `subEvent[]` does for `permanent` offers, and is refreshed by the same
`udb3-core:reindex-permanent` console command.

### The count respects closed and adjusted days

`dayOfWeekHits` counts only days the offer is **actually open**, with closed and adjusted days applied:

- A weekday occurrence that falls inside `openingHoursClosedDays` does **not** count.
- An adjusted day counts only if the adjusted opening hours still leave that weekday open.

So `dayOfWeekHits.wednesday >= 4` means "this offer has real, bookable occurrences on at least four
Wednesdays," not merely "Wednesday is in the recurring pattern."

### Search parameter

The threshold ("how many occurrences count as recurring") is **not** baked into the index. It is a
constructor parameter of `ElasticSearchOfferQueryBuilder` (default `4`) applied as a range filter
**at query time**, so it can change without a reindex.

| Parameter | ES field | Behaviour |
|---|---|---|
| `dayOfWeek=wednesday` | `dayOfWeekHits.wednesday` | Only offers open on ≥ `N` Wednesdays. |
| `dayOfWeek=friday,saturday,sunday` | `dayOfWeekHits.{friday,saturday,sunday}` | OR-combined: open on ≥ `N` of **any** of those weekdays. |
| _(omitted)_ | — | No day-of-week filtering. |

```
GET /offers?dayOfWeek=friday,saturday
```
→ runs a `bool`/`should` of `range` queries (`gte: N`), one per requested weekday, as a top-level
filter. Values are comma-separated (consistent with `attendanceMode`, `workflowStatus`) and
case-insensitive (`Wednesday` is accepted). An unknown weekday is rejected with a validation error.

---

## Closed days and adjusted days

Two fields that the backend model supports but are **not yet handled by the indexer**.

### Closed days

A closed day is a date range where the offer is not available, even if it would normally be open on those days.

```json
{
  "openingHoursClosedDays": [
    {
      "startDate": "2024-07-15T00:00:00+00:00",
      "endDate": "2024-07-19T23:59:59+00:00",
      "description": {
        "nl": "Zomervakantie",
        "en": "Summer break"
      }
    }
  ]
}
```

With the current indexer, the week of 15–19 July would still appear in `subEvent[]` as available. That is wrong.

### Adjusted days

An adjusted day is a date range where the offer uses different opening hours than usual.

```json
{
  "openingHoursAdjustedDays": [
    {
      "startDate": "2024-12-24T00:00:00+00:00",
      "endDate": "2024-12-24T23:59:59+00:00",
      "openingHours": [
        { "dayOfWeek": ["tuesday"], "opens": "09:00", "closes": "13:00" }
      ],
      "description": {
        "nl": "Kerstavond — vroeger gesloten",
        "en": "Christmas Eve — closes early"
      }
    }
  ]
}
```

With the current indexer, 24 December would use the regular opening hours. That is wrong.

Both fields only apply to `periodic` and `permanent` calendars. `single` and `multiple` carry explicit sub-events and are not affected.

---

## How closed and adjusted days change sub-event generation

Neither field gets indexed on its own. They are source-only, just like `openingHours`. The indexer reads them, uses them to decide which sub-events to generate, and then discards them. The indexed surface stays the same. No mapping changes and no re-index migration needed.

### Closed days

This is straightforward. When generating sub-events, skip any day that falls within a closed range.

**Example: periodic with a closed day**

```json
{
  "calendarType": "periodic",
  "startDate": "2026-05-04T00:00:00+00:00",
  "endDate": "2026-05-08T23:59:59+00:00",
  "openingHours": [
    { "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"], "opens": "09:00", "closes": "17:00" }
  ],
  "openingHoursClosedDays": [
    { "startDate": "2026-05-06", "endDate": "2026-05-06" }
  ]
}
```

Without closed days: 5 sub-events (Mon 4 to Fri 8 May, one per day).
With closed days: 4 sub-events. Wednesday 6 May is skipped.

A search for events on 6 May will not return this offer, because there is no sub-event for that date.

### Adjusted days

This is more involved. When a day falls within an adjusted range, it still gets a sub-event, but built from the adjusted opening hours instead of the regular ones.

**Example: one day closes early**

```json
{
  "calendarType": "periodic",
  "startDate": "2026-05-04T00:00:00+00:00",
  "endDate": "2026-05-08T23:59:59+00:00",
  "openingHours": [
    { "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"], "opens": "09:00", "closes": "17:00" }
  ],
  "openingHoursAdjustedDays": [
    {
      "startDate": "2026-05-06",
      "endDate": "2026-05-06",
      "openingHours": [
        { "dayOfWeek": ["wednesday"], "opens": "09:00", "closes": "12:00" }
      ],
      "description": { "nl": "Vroeg gesloten", "en": "Early closing" }
    }
  ]
}
```

Wednesday 6 May gets a sub-event from 09:00 to 12:00 instead of 09:00 to 17:00. A search for events between 13:00 and 17:00 on 6 May will not return this offer.

**Why this is complex:**

The adjusted opening hours are still structured per `dayOfWeek`. The indexer has to match the actual day of week of each date against the adjusted hours, using the same expansion logic as regular opening hours but scoped to the adjusted range only.

**API constraints (enforced by the backend validators, not JSON schema):**

- Adjusted day entries must not overlap each other. The backend rejects overlapping ranges, so the indexer will never see two adjusted entries that cover the same date.
- A date can still fall in both a closed range and an adjusted range. Closed days take precedence.
- For `periodic` calendars, both closed days and adjusted days must fall within the calendar's `startDate`–`endDate` range.

---

## Code reference

### Indexing

- `CalendarTransformer`: transforms the source calendar into indexed fields. Key methods: `transformDateRange()`, `transformLocalTimeRange()`, `transformSubEvents()`, 
- `transformHasChildcare()`, `polyFillJsonLdSubEvents()`. It also writes `dayOfWeekHits` from the same `EffectiveOpeningHoursResolver::resolve()` call it uses to build `subEvent[]`.
- `EffectiveOpeningHoursResolver` / `EffectiveOpeningHours`: resolve the effective (closures/adjustments applied) opening hours once. `EffectiveOpeningHours::slots()` feeds `subEvent[]`; `EffectiveOpeningHours::dayCounts()` feeds `dayOfWeekHits`.
- `SubEventCapTransformer`: runs immediately after `CalendarTransformer` in `OfferTransformer` and caps `subEvent` to `SubEventCapTransformer::DEFAULT_CAP` entries to stay under Elasticsearch's nested-object limit.

### Elasticsearch mappings

- `mapping_udb3_core.json`: shared core mapping (ES8)
- `mapping_event.json`: field mapping for events
- `mapping_place.json`: field mapping for places

### Query building

- `CalendarOfferRequestParser`: decides whether to use a top-level or a nested query based on which parameters are combined.
- `HasChildcareOfferRequestParser`: parses the `hasChildcare` boolean parameter.
- `DayOfWeekOfferRequestParser` / `DayOfWeek`: parses the comma-separated, case-insensitive `dayOfWeek` parameter into `DayOfWeek` value objects.
- `ElasticSearchOfferQueryBuilder`: builds the actual Elasticsearch queries. Key methods: `withDateRangeFilter()`, `withLocalTimeRangeFilter()`, `withStatusFilter()`, `withBookingAvailabilityFilter()`, `withAvailableRangeFilter()`, `withSubEventFilter()`, `withHasChildcareFilter()`, `withDayOfWeekFilter()`. The `dayOfWeek` threshold is a constructor parameter (default `4`).
- `SubEventQueryParameters`: collects the combined sub-event filter parameters before passing them to the query builder.

### Backend calendar model (udb3-backend)

- `ClosedDay`: holds `startDate`, `endDate`, and an optional description.
- `AdjustedDay`: holds `startDate`, `endDate`, its own `openingHours`, and an optional description.
