# Prompt: write feature (Behat) tests for the `hasChildcare` search filter

Use the prompt below in the **udb3-backend** repository (that is where the end-to-end
Behat search feature tests live, under `features/search/`). It drives an agent to add
black-box API feature tests for the new `hasChildcare` offer search parameter that was
implemented in udb3-search-service (PR #493, branch `III-7215/childcare-filter`).

---

## What scenario 2 means

The filter has two modes depending on whether a date range is also present in the request.

**Without a date range (`?hasChildcare=true` alone)**
The filter checks the top-level `hasChildcare` field, which is `true` when *at least one*
sub-event or opening hour on the offer has a childcare range configured, regardless of when
that sub-event falls. This is a fast, document-level boolean check.

**With a date range (`?hasChildcare=true&dateFrom=X&dateTo=Y`)**
The filter shifts to a nested sub-event query. It returns the offer only when there exists
a sub-event that *simultaneously* satisfies both conditions:
- its `dateRange` overlaps the requested window, **and**
- it has `hasChildcare = true`.

A sub-event on a different day with childcare does **not** make the offer match. This is
the key distinction from scenario 1, which would have returned the offer in that case.

Concrete example: Event A has two sub-events — 2026-01-01 (with childcare) and 2026-01-08
(without childcare).

| Query                                    | Scenario 1 | Scenario 2 |
|------------------------------------------|------------|------------|
| `hasChildcare=true`                      | matches    | matches    |
| `dateFrom=2026-01-01&hasChildcare=true`  | matches    | matches    |
| `dateFrom=2026-01-08&hasChildcare=true`  | matches ✗  | no match ✓ |

Scenario 1 returned a false positive on the third row because it only checked the
top-level boolean, ignoring *which* sub-event falls in the requested window. Scenario 2
avoids this by joining the date and childcare conditions inside a single nested query.

This is the same pattern already used for `status` and `bookingAvailability` when combined
with `dateFrom`/`dateTo` (see `CalendarOfferRequestParser`).

---

## Prompt

> You are working in the **udb3-backend** repository. Add Behat feature tests for the
> `hasChildcare` offer search parameter that has just been added to the search API
> (udb3-search-service). These are end-to-end tests: they create events via the write API,
> wait for them to be indexed, and then assert search results.
>
> ### Feature under test
>
> `hasChildcare` is a boolean query parameter on `GET /events` (and the shared offer search).
>
> **Without a date range:**
> - `hasChildcare=true` → only offers with *at least one* sub-event or opening hour that has
>   a `childcare` range configured are returned.
> - `hasChildcare=false` → only offers with *no* childcare configured anywhere are returned.
>
> **With `dateFrom`/`dateTo` also set:**
> - `hasChildcare=true` → only offers with a sub-event that *both* falls in the requested
>   date window *and* has childcare are returned. An offer whose childcare sub-event is
>   outside the requested window does NOT match.
> - `hasChildcare=false` with dates → only offers with a sub-event in that window that also
>   has no childcare are returned.
>
> Childcare is **event-only**. Places never have childcare. Childcare hours must **not**
> affect the effective time of the event: they do not extend or shift `dateRange`, and they
> must not change which events match `dateFrom`/`dateTo`/`localTimeFrom`/`localTimeTo`.
>
> ### Childcare JSON-LD shape (write payload)
>
> A `childcare` object (`{"start": "HH:MM", "end": "HH:MM"}`, either field optional) can
> sit on:
> - a `subEvent` entry (calendar types `single`, `multiple`), and
> - an `openingHours` entry (calendar types `periodic`, `permanent`).
>
> See existing fixtures for the exact shape:
> - `features/data/events/sub-event-childcare/*.json`
> - `features/data/events/opening-hours-childcare/*.json`
>
> ### How to write the tests
>
> - Create a new feature file `features/search/childcare.feature`, tagged `@sapi3`.
> - Model it on `features/search/calendar-overrides.feature`: same `Background`
>   (UDB3 base URL, UiTID v1 API key of consumer "uitdatabank", authorized as JWT provider
>   user "centraal_beheerder", `application/json`, create a minimal place saved as
>   `placeUrl`, wait for it to be indexed), and the same create → `keep url as "eventUrl"`
>   → `wait for the event with url "%{eventUrl}" to be indexed` → `GET /events with
>   parameters` flow. Always scope each search with `q = %{eventUrl}` and
>   `disableDefaultFilters = true` so assertions are deterministic (`totalItems` is 0 or 1).
>   Reuse existing Gherkin step definitions only — do not invent new steps.
>
> - Cover these scenarios (one event created per scenario unless noted):
>
>   1. **Single event with sub-event childcare** → `hasChildcare=true` returns it
>      (`totalItems` 1), `hasChildcare=false` does not (`totalItems` 0).
>
>   2. **Multiple event, childcare on only one sub-event** → `hasChildcare=true` returns it;
>      `hasChildcare=false` does not. (A single configured range flags the whole offer.)
>
>   3. **Periodic event with childcare on an opening hour** → `hasChildcare=true` returns it;
>      `hasChildcare=false` does not.
>
>   4. **Permanent event with childcare on an opening hour** → `hasChildcare=true` returns
>      it; `hasChildcare=false` does not.
>
>   5. **Event without any childcare** → `hasChildcare=false` returns it; `hasChildcare=true`
>      does not.
>
>   6. **Childcare does not affect the date filter (regression):** create a `single`/`multiple`
>      event whose `childcare` range is wider than the activity's start/end times (e.g.
>      childcare 08:00–19:00 around a 10:00–18:00 activity). Then:
>      - a `dateFrom`/`dateTo` search covering only the *activity* window returns the event,
>        and
>      - a `dateFrom`/`dateTo` search covering only the childcare-only window (e.g.
>        08:00–09:59, outside the activity) returns `totalItems` 0.
>      This proves childcare does not extend `dateRange`.
>
>   7. **Scenario 2 — date + childcare only matches the right sub-event:** create a
>      `multiple` event with two sub-events:
>      - Sub-event 1: 2026-01-01, **with** childcare.
>      - Sub-event 2: 2026-01-08, **without** childcare.
>      Then assert:
>      - `hasChildcare=true` alone → event is returned (top-level flag is true).
>      - `dateFrom=2026-01-01&dateTo=2026-01-01&hasChildcare=true` → event is returned
>        (the sub-event on that date has childcare).
>      - `dateFrom=2026-01-08&dateTo=2026-01-08&hasChildcare=true` → event is **not**
>        returned (the sub-event on that date has no childcare, even though the offer has
>        childcare on a different day).
>      This scenario is the key regression guard for scenario 2 vs scenario 1.
>
>   8. **Places are unaffected:** a place is never returned by `hasChildcare=true`.
>
> - Add any new payload fixtures under `features/data/events/childcare-search/` (or reuse
>   the existing childcare fixtures where they fit), following the placeholder convention
>   (`%{placeUrl}`, `%{childcareStart}`, …) already used in the repo.
>
> - Use future, timezone-explicit dates (see the `2026-...+02:00` style in
>   `calendar-overrides.feature`) so the events stay in the indexable/searchable window.
>
> ### Deliverables
>
> - `features/search/childcare.feature` with the scenarios above.
> - Any supporting JSON fixtures.
> - Run the search feature suite (the project's Behat `@sapi3` runner) and make the new
>   scenarios pass. Report the exact command you used and the result.
>
> Do not modify application code — these are black-box tests against the running API. If a
> scenario cannot pass because of a product gap, stop and report it rather than weakening
> the assertion.

---

## Notes / context for the reviewer

- Implementation reference (udb3-search-service PR #493):
  - Parsing: `CalendarOfferRequestParser` reads `hasChildcare` alongside `dateFrom`/`dateTo`
    and `status`. When dates are present, it passes `hasChildcare` through
    `SubEventQueryParameters` into `withSubEventFilter()` (a nested ES query on
    `subEvent.hasChildcare`). When no dates are present, it calls
    `withHasChildcareFilter()` (a top-level `term` query on `hasChildcare`).
  - Indexing: `CalendarTransformer` writes the top-level `hasChildcare` boolean *and*
    `hasChildcare` on each individual indexed sub-event (derived from whether the source
    sub-event has a `childcare` key). Opening-hours-based sub-events generated by the
    poly-fill do not carry childcare (the poly-fill strips it), so `hasChildcare` is
    `false` on those even if the source opening hour had childcare — only the top-level
    field reflects opening-hour childcare.
- The search-service unit/integration coverage already exists (`CalendarTransformerTest`,
  `ElasticSearchOfferQueryBuilderTest`, `OfferSearchControllerTest`). The Behat tests
  requested here are the missing **end-to-end** layer proving the write→index→search path
  works against a live Elasticsearch.
