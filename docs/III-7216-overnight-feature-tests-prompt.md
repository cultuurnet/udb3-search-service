# Prompt: write feature (Behat) tests for the `hasOvernight` search filter

Use the prompt below in the **udb3-backend** repository (that is where the end-to-end
Behat search feature tests live, under `features/search/`). It drives an agent to add
black-box API feature tests for the new `hasOvernight` offer search parameter that was
implemented in udb3-search-service (branch `III-7216/overnight-filter`).

---

## Prompt

> You are working in the **udb3-backend** repository. Add Behat feature tests for a new
> offer search parameter, `hasOvernight`, that has just been added to the search API
> (udb3-search-service). These are end-to-end tests: they create events via the write API,
> wait for them to be indexed, and then assert search results.
>
> ### Feature under test
>
> `hasOvernight` is a boolean query parameter on `GET /events` (and the shared offer search).
> - `hasOvernight=true` → only offers that have **at least one** sub-event with `overnight: true`
>   are returned.
> - `hasOvernight=false` → only offers with **no** sub-event flagged `overnight: true` are returned.
> - Omitting the parameter → behaviour is unchanged (no overnight filtering).
>
> **Rollup rule (event level):** the filter couples on event level. If at least one sub-event has
> `overnight: true`, the whole event counts as having overnight. A partial overnight event (some
> sub-events `true`, some not) therefore matches `hasOvernight=true`.
>
> Overnight is **event-only** and lives on sub-events only. Opening hours never carry it, and
> places never have overnight. Overnight must **not** affect the effective time of the event: it
> does not extend or shift `dateRange`, and it must not change which events match
> `dateFrom`/`dateTo`/`localTimeFrom`/`localTimeTo`/`status`.
>
> ### Overnight JSON-LD shape (write payload)
>
> `overnight` is a boolean on a `subEvent` entry (calendar types `single`, `multiple`). It is only
> meaningful when `true`; when the stay is not overnight the field is either absent or `false`.
>
> ```json
> {
>   "calendarType": "multiple",
>   "subEvent": [
>     { "startDate": "...", "endDate": "...", "overnight": true },
>     { "startDate": "...", "endDate": "..." }
>   ]
> }
> ```
>
> Check the backend calendar model / serializer (`SubEvent`, `SubEventNormalizer`,
> `SubEventDenormalizer`) for the exact accepted shape, and look at existing calendar fixtures
> under `features/data/events/` for the surrounding payload conventions.
>
> ### How to write the tests
>
> - Create a new feature file `features/search/overnight.feature`, tagged `@sapi3`.
> - Model it on `features/search/calendar-overrides.feature` (or the childcare feature if that
>   already landed): same `Background` (UDB3 base URL, UiTID v1 API key of consumer "uitdatabank",
>   authorized as JWT provider user "centraal_beheerder", `application/json`, create a minimal place
>   saved as `placeUrl`, wait for it to be indexed), and the same create →
>   `keep url as "eventUrl"` → `wait for the event with url "%{eventUrl}" to be indexed` →
>   `GET /events with parameters` flow. Always scope each search with `q = %{eventUrl}` and
>   `disableDefaultFilters = true` so assertions are deterministic (`totalItems` is 0 or 1). Reuse
>   existing Gherkin step definitions only — do not invent new steps.
>
> - Cover these scenarios (one event created per scenario unless noted):
>   1. **Single event with an overnight sub-event** → `hasOvernight=true` returns it (`totalItems` 1),
>      `hasOvernight=false` does not (`totalItems` 0).
>   2. **Multiple event, `overnight: true` on only one sub-event** → `hasOvernight=true` returns it;
>      `hasOvernight=false` does not. (A single overnight sub-event flags the whole offer.)
>   3. **Multiple event where every sub-event is overnight** → `hasOvernight=true` returns it;
>      `hasOvernight=false` does not.
>   4. **Event without any overnight sub-event** (e.g. plain single/multiple, or `overnight: false`)
>      → `hasOvernight=false` returns it; `hasOvernight=true` does not.
>   5. **Periodic event with opening hours** (no overnight possible) → `hasOvernight=false` returns
>      it; `hasOvernight=true` does not.
>   6. **Overnight does not affect the date filter (regression):** create a `single`/`multiple`
>      event with an overnight sub-event, then confirm a `dateFrom`/`dateTo` search covering the
>      sub-event's own start/end window still returns it, and that overnight has not widened the
>      matched range beyond the sub-event's actual `startDate`/`endDate`.
>   7. **Combine `hasOvernight=true` with a matching `dateFrom`/`dateTo`** and confirm both
>      conditions hold together (event returned), and that `hasOvernight=false` with the same
>      date window returns 0.
>   8. **Places are unaffected:** a place is never returned by `hasOvernight=true`.
>
> - Add any new payload fixtures under `features/data/events/overnight-search/` (or reuse existing
>   calendar fixtures where they fit), following the placeholder convention (`%{placeUrl}`, …)
>   already used in the repo.
>
> - Use future, timezone-explicit dates (see the `2026-...+02:00` style in
>   `calendar-overrides.feature`) so the events stay in the indexable/searchable window.
>
> ### Deliverables
>
> - `features/search/overnight.feature` with the scenarios above.
> - Any supporting JSON fixtures.
> - Run the search feature suite (the project's Behat `@sapi3` runner) and make the new
>   scenarios pass. Report the exact command you used and the result.
>
> Do not modify application code — these are black-box tests against the running API. If a
> scenario cannot pass because of a product gap, stop and report it rather than weakening the
> assertion.

---

## Notes / context for the reviewer

- Implementation reference (udb3-search-service): the parameter is parsed by
  `HasOvernightOfferRequestParser`, filtered via `ElasticSearchOfferQueryBuilder::withHasOvernightFilter()`
  (a `term` query on the top-level `hasOvernight` boolean), and indexed by `CalendarTransformer`
  (top-level `hasOvernight`, derived from the source sub-events before poly-filling, so it never
  influences `dateRange`/`localTimeRange`/`subEvent`).
- Overnight lives on sub-events only (`single`/`multiple`); opening hours and places never carry
  it, so `hasOvernight` is always indexed as `false` for places.
- The search-service unit/integration coverage already exists (`CalendarTransformerTest`,
  `ElasticSearchOfferQueryBuilderTest`, `OfferSearchControllerTest`). The Behat tests requested
  here are the missing **end-to-end** layer proving the write→index→search path works against a
  live Elasticsearch.
